<?php

namespace Feature\Chat;

use App\Http\Controllers\Chat\ChatController;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\Contact;
use App\Models\ContactChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ChatController::class)]
class SendMessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Chatbot $chatbot;

    private ChatbotChannel $chatbotChannel;

    private Conversation $conversation;

    private MessageTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->user = User::where('email', 'agent@example.com')->firstOrFail();
        $this->chatbot = Chatbot::firstOrFail();

        // Setup a conversation with a contact
        $contact = Contact::factory()->create(['organization_id' => $this->user->organizations()->first()->id]);
        $contactChannel = ContactChannel::factory()->create([
            'contact_id' => $contact->id,
            'channel_id' => 1, // WhatsApp
        ]);

        $this->chatbotChannel = $this->chatbot->chatbotChannels()->first();
        $phoneNumberId = '918518644364527';
        $this->chatbotChannel->update(
            [
                'credentials' => [
                    'phone_number' => '+3333333333',
                    'phone_number_id' => $phoneNumberId,
                    'phone_number_access_token' => 'EAAJBsdgh',
                    'whatsapp_business_account_id' => '102290129340398',
                    'whatsapp_business_access_token' => 'EAAJBsdgh',
                ],
                'webhook_url' => 'https://graph.facebook.com/v24.0/',
            ]
        );
        $this->conversation = Conversation::factory()->create([
            'contact_channel_id' => $contactChannel->id,
            'chatbot_channel_id' => $this->chatbotChannel->id,
        ]);

        // Create an approved template with mappings
        $this->template = MessageTemplate::factory()->create([
            'chatbot_channel_id' => $this->conversation->chatbot_channel_id,
            'status' => 'approved',
            'variable_mappings' => [
                'body' => [
                    ['placeholder' => '{{name}}', 'source' => 'manual', 'label' => 'Name'],
                ],
            ],
        ]);

        $this->actingAs($this->user);
        Event::fake();
    }

    #[Test]
    public function it_successfully_sends_a_template_message()
    {
        // Fake the WhatsApp API response
        Http::fake([
            '*/messages' => Http::response([
                'messages' => [['id' => 'wamid.HBgL...']],
            ], 200),
        ]);

        $payload = [
            'template_id' => $this->template->id,
            'manual_values' => [
                'body' => [
                    'name' => 'John Doe',
                ],
            ],
        ];

        $response = $this->post(route('chats.messages.send-template', $this->conversation->id), $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message.content', $this->template->body_content);

        // 1. Verify Message was created in DB
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $this->conversation->id,
            'external_message_id' => 'wamid.HBgL...',
            'sender_type' => 'human',
        ]);

        $message = Message::latest('id')->first();
        $this->assertTrue($message->isFromTemplate());

        // 2. Verify MessageTemplateSend was created
        $this->assertDatabaseHas('message_template_sends', [
            'message_id' => $message->id,
            'message_template_id' => $this->template->id,
            'send_status' => 'sent',
        ]);

        // 3. Verify Template usage was incremented
        $this->assertEquals(1, $this->template->refresh()->usage_count);
    }

    #[Test]
    public function it_records_failure_when_whatsapp_api_fails()
    {
        // Fake a failure from Meta API
        Http::fake([
            '*/messages' => Http::response(['error' => 'Invalid parameters'], 400),
        ]);

        $payload = ['template_id' => $this->template->id];

        $response = $this->post(route('chats.messages.send-template', $this->conversation->id), $payload);

        // Even if API fails, we return the message object so it shows in the chat
        $response->assertStatus(200);

        $message = Message::latest('id')->first();
        $this->assertNotNull($message->failed_at);
        $this->assertNotNull($message->error_message);

        // Trazabilidad also shows failure
        $this->assertDatabaseHas('message_template_sends', [
            'message_id' => $message->id,
            'send_status' => 'failed',
        ]);
    }

    #[Test]
    public function it_sends_the_exact_payload_expected_by_meta_documentation()
    {
        // This is based in the example provided in the Meta documentation:
        // https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/utility-templates/utility-templates

        // 1. Setup: Create the "reservation_confirmation" template based on the WABA creation payload
        $template = MessageTemplate::factory()->create([
            'chatbot_channel_id' => $this->conversation->chatbot_channel_id,
            'name' => 'reservation_confirmation',
            'language' => 'en_US',
            'header_type' => 'image',
            'status' => 'approved',
            'example_data' => [
                'header_handle' => ['4::aW...'],
                'body_text_named_params' => [
                    ['param_name' => 'number_of_guests', 'example' => '4'],
                    ['param_name' => 'day', 'example' => 'Saturday'],
                    ['param_name' => 'date', 'example' => 'August 30th, 2025'],
                    ['param_name' => 'time', 'example' => '7:30 pm'],
                ],
            ],
            'variable_mappings' => [
                'header' => ['placeholder' => '{{1}}', 'source' => 'manual', 'label' => 'Header Image ID'],
                'body' => [
                    ['placeholder' => '{{number_of_guests}}', 'source' => 'manual', 'label' => 'Guests'],
                    ['placeholder' => '{{day}}', 'source' => 'manual', 'label' => 'Day'],
                    ['placeholder' => '{{date}}', 'source' => 'manual', 'label' => 'Date'],
                    ['placeholder' => '{{time}}', 'source' => 'manual', 'label' => 'Time'],
                ],
            ],
        ]);

        // 2. Setup: Adjust the contact's phone to match the example
        $this->conversation->contact_phone = '16505551234';
        $this->conversation->save();

        // 3. Fake API
        Http::fake([
            '*/messages' => Http::response(['messages' => [['id' => 'wamid.HBgL...']]], 200),
        ]);

        // 4. Action: Send the template with the example values
        $payload = [
            'template_id' => $template->id,
            'manual_values' => [
                'header' => [
                    '1' => '2871834006348767', // Image ID in the header
                ],
                'body' => [
                    'number_of_guests' => '4',
                    'day' => 'Saturday',
                    'date' => 'August 30th, 2025',
                    'time' => '7:30 pm',
                ],
            ],
        ];

        $this->post(route('chats.messages.send-template', $this->conversation->id), $payload);

        // 5. Assert: Verify that the payload sent to Meta matches the documentation exactly
        Http::assertSent(function ($request) {
            $data = $request->data();
            $components = collect($data['template']['components']);
            $header = $components->firstWhere('type', 'header');
            $body = $components->firstWhere('type', 'body');

            return $data['type'] === 'template' &&
                $data['to'] === '16505551234' &&
                $data['template']['name'] === 'reservation_confirmation' &&
                $data['template']['language']['code'] === 'en_US' &&
                // Header Check
                $data['template']['components'][0]['type'] === 'header' &&
                $data['template']['components'][0]['parameters'][0]['type'] === 'image' &&
                $data['template']['components'][0]['parameters'][0]['image']['id'] === '2871834006348767' &&
                // Body Check
                $data['template']['components'][1]['type'] === 'body' &&
                count($data['template']['components'][1]['parameters']) === 4 &&
                $data['template']['components'][1]['parameters'][0]['parameter_name'] === 'number_of_guests' &&
                $data['template']['components'][1]['parameters'][0]['text'] === '4' &&
                $data['template']['components'][1]['parameters'][3]['parameter_name'] === 'time' &&
                $data['template']['components'][1]['parameters'][3]['text'] === '7:30 pm' &&
                $header['parameters'][0]['image']['id'] === '2871834006348767' &&
                $body['parameters'][0]['parameter_name'] === 'number_of_guests';
        });
    }

    #[Test]
    public function it_sends_the_correct_payload_for_positional_templates()
    {
        // 1. Setup: Create a positional template (based on the actual structure of Meta)
        $template = MessageTemplate::factory()->create([
            'chatbot_channel_id' => $this->conversation->chatbot_channel_id,
            'name' => 'positional_template_test',
            'language' => 'en_US',
            'header_type' => 'text',
            'status' => 'approved',
            'example_data' => [
                'header_text' => ['Cris'],
                'body_text' => [['JoboGroup', 'Erika']],
            ],
            'variable_mappings' => [
                'header' => ['placeholder' => '{{1}}', 'source' => 'manual', 'label' => '1'],
                'body' => [
                    ['placeholder' => '{{1}}', 'source' => 'manual', 'label' => '1'],
                    ['placeholder' => '{{2}}', 'source' => 'manual', 'label' => '2'],
                ],
            ],
        ]);

        // 2. Fake API
        Http::fake([
            '*/messages' => Http::response(['messages' => [['id' => 'wamid.123']]], 200),
        ]);

        // 3. Action: Send with manual values (unsorted to test usort)
        $payload = [
            'template_id' => $template->id,
            'manual_values' => [
                'header' => ['1' => 'Cris Header'],
                'body' => [
                    '2' => 'Erika Body', // We send the 2 before the 1
                    '1' => 'JoboGroup Body',
                ],
            ],
        ];

        $this->post(route('chats.messages.send-template', $this->conversation->id), $payload);

        // 4. Assert: Verify the exact payload for positional
        Http::assertSent(function ($request) {
            $data = $request->data();
            $bodyParams = $data['template']['components'][1]['parameters'];

            return $data['type'] === 'template' &&
                // We verify that parameter_name does NOT exist
                ! isset($bodyParams[0]['parameter_name']) &&
                ! isset($bodyParams[1]['parameter_name']) &&
                // We verified the ORDER (usort working)
                $bodyParams[0]['text'] === 'JoboGroup Body' &&
                $bodyParams[1]['text'] === 'Erika Body';
        });
    }
}
