<?php

namespace App\Jobs;

use App\Contracts\Services\AI\AIServiceInterface;
use App\Contracts\Services\Chat\MessageServiceInterface;
use App\Contracts\Services\Util\PhoneServiceInterface;
use App\Mail\Notification\DeveloperNotification;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAIResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Message $message)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        AIServiceInterface $aiService,
        MessageServiceInterface $messageService,
        PhoneServiceInterface $phoneService
    ): void {
        if ($this->message->content_type !== 'text') {
            return;
        }
        $phoneNumber = $this->message->conversation->contact_phone
            ?? $this->message->conversation->contactChannel->contact->phone_number
            ?? '';

        $countryCode = '';
        if ($phoneNumber) {
            $countryCode = $phoneService->getCountryFromPhoneNumber($phoneNumber);
        }
        try {
            // Get conversation history
            $history = $this->getConversationHistory();

            // Get prompt from ChatbotChannel
            $prompt = $this->message->conversation->chatbotChannel->chatbot->system_prompt ??
                'You are a helpful assistant. Respond professionally and concisely.';

            if ($countryCode) {
                $prompt .= "\n".'If you cannot determine the language from the conversation history, use the principal language of the country. The country code is'.strtoupper($countryCode)."\n";
            }

            // Generate AI response
            $aiResponse = $aiService->generateResponse($prompt, $history);

            Log::info('AI Response: '.$aiResponse);

            $messageData = [
                'content' => $aiResponse,
                'content_type' => 'text',
                'sender_type' => 'ai',
                'metadata' => [
                    'timestamp' => now()->timestamp,
                ],
            ];

            $messageService->createAndSendOutgoingMessage($this->message->conversation, $messageData);

        } catch (\Exception $e) {
            Log::error('Error processing AI response', [
                'error' => $e->getMessage(),
                'message_id' => $this->message->id,
                'attempt' => $this->attempts(),
            ]);

            throw $e; // This will trigger a retry if attempts are remaining
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $recipients = array_filter(config('notifications.ai_processing_failure.recipients'));
        if (empty($recipients)) {
            Log::critical('AI response processing permanently failed, but no recipients configured.', [
                'message_id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
            ]);

            return;
        }

        $subject = 'CRITICAL: AI Response Processing Failed for Conversation '.$this->message->conversation_id;
        $body = "AI response processing for message ID {$this->message->id} and conversation ID {$this->message->conversation_id} has failed after all attempts.\n\n";
        $body .= "Error: {$exception->getMessage()}\n";
        $body .= 'Please investigate the logs for more details.';

        Mail::to($recipients)->send(new DeveloperNotification($subject, $body));

        Log::critical('AI response processing permanently failed and notification sent', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'recipients' => $recipients,
        ]);
    }

    /**
     * Get formatted conversation history
     *
     * @return array<array{role: string, content: string}>
     */
    private function getConversationHistory(): array
    {
        return $this->message->conversation
            ->messages()
            ->latest()
            ->limit(100)
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->sender_type === 'contact' ? 'user' : 'assistant',
                    'content' => $message->content,
                ];
            })
            ->toArray();
    }
}
