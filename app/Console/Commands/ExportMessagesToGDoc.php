<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Google\Client;
use Google\Service\Docs;
use Google\Service\Docs\Document;
use Google\Service\Docs\Request;
use Google\Service\Docs\BatchUpdateDocumentRequest;

class ExportMessagesToGDoc extends Command
{
    /**
     * Batch size
     */
    private const BATCH_SIZE = 1000;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'export:messages
                            {chatbot_id : ID del chatbot}
                            {channel_id : ID del channel}';

    /**
     * The console command description.
     */
    protected $description = 'Export filtered messages by chatbot and channel to Google Docs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $chatbotId = $this->argument('chatbot_id');
        $channelId = $this->argument('channel_id');

        $this->info('Fetching messages in batches...');

        /*
        |--------------------------------------------------------------------------
        | 1. Get ID range
        |--------------------------------------------------------------------------
        */

        $idRange = DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->join('chatbot_channels as cc', 'cc.id', '=', 'c.chatbot_channel_id')
            ->where('cc.chatbot_id', $chatbotId)
            ->where('cc.channel_id', $channelId)
            ->selectRaw('MIN(m.id) as min_id, MAX(m.id) as max_id')
            ->first();

        if (!$idRange || !$idRange->min_id) {
            $this->warn('No messages found.');
            return Command::SUCCESS;
        }

        $currentFromId = (int) $idRange->min_id;
        $maxId = (int) $idRange->max_id;

        $this->info("Processing messages from ID {$currentFromId} to {$maxId}");

        /*
        |--------------------------------------------------------------------------
        | 2. Headers (Spanish as requested)
        |--------------------------------------------------------------------------
        */

        $lines = [];
        $lines[] = implode(' | ', [
            'ID Organización',
            'ID Agente',
            'ID Conversación',
            'Canal',
            'Nombre Usuario',
            'Mensaje Usuario',
            'Respuesta IA/Agente',
            'Timestamp'
        ]);

        /*
        |--------------------------------------------------------------------------
        | 3. Process batches using WHILE
        |--------------------------------------------------------------------------
        */

        while ($currentFromId <= $maxId) {

            $currentToId = $currentFromId + self::BATCH_SIZE - 1;

            $this->info("Batch: {$currentFromId} - {$currentToId}");

            $rows = DB::table('messages as m')
                ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
                ->join('chatbot_channels as cc', 'cc.id', '=', 'c.chatbot_channel_id')
                ->join('channels as ch', 'ch.id', '=', 'cc.channel_id')
                ->where('cc.chatbot_id', $chatbotId)
                ->where('cc.channel_id', $channelId)
                ->where('m.id', '>', $currentFromId - 1)
                ->where('m.id', '<=', $currentToId)
                ->orderBy('m.id')
                ->select([
                    'cc.channel_id as organization_id',
                    'cc.chatbot_id as chatbot_id',
                    'm.conversation_id',
                    'ch.name as channel_name',
                    'c.contact_name as user_name',
                    'm.type',
                    'm.content',
                    'm.created_at',
                ])
                ->get();

            if ($rows->isNotEmpty()) {
                foreach ($rows as $row) {
                    $lines[] = implode(' | ', [
                        $row->organization_id,
                        $row->chatbot_id,
                        $row->conversation_id,
                        $row->channel_name,
                        $row->user_name ?? '',
                        $row->type === 'incoming' ? $row->content : '',
                        $row->type === 'outcoming' ? $row->content : '',
                        $row->created_at,
                    ]);
                }
            }

            // Move to next batch
            $currentFromId += self::BATCH_SIZE;
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Final content
        |--------------------------------------------------------------------------
        */

        $content = implode("\n", $lines);

        // Debug (optional)
        // echo $content;
        // die();

        /*
        |--------------------------------------------------------------------------
        | 5. Google Docs Authentication
        |--------------------------------------------------------------------------
        */

        $this->info('Creating Google Docs document...');

        $client = new Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->setScopes([Docs::DOCUMENTS]);

        $docsService = new Docs($client);

        /*
        |--------------------------------------------------------------------------
        | 6. Create document
        |--------------------------------------------------------------------------
        */

        $document = new Document([
            'title' => "Export mensajes chatbot {$chatbotId} - channel {$channelId}",
        ]);

        $doc = $docsService->documents->create($document);
        $documentId = $doc->getDocumentId();

        /*
        |--------------------------------------------------------------------------
        | 7. Insert content
        |--------------------------------------------------------------------------
        */

        $requests = [
            new Request([
                'insertText' => [
                    'location' => [
                        'index' => 1,
                    ],
                    'text' => $content,
                ],
            ]),
        ];

        $docsService->documents->batchUpdate(
            $documentId,
            new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ])
        );

        $this->info('Document created successfully.');
        $this->info("Document ID: {$documentId}");

        return Command::SUCCESS;
    }
}
