<?php

namespace Feature\Services\WhatsApp;

use App\Models\Channel;
use App\Models\Chatbot;
use App\Models\ChatbotChannel;
use App\Models\MessageTemplate;
use App\Models\MessageTemplateCategory;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppService $whatsAppService;

    private User $user;

    private Chatbot $chatbot;

    private ChatbotChannel $channel;

    private MessageTemplateCategory $marketingCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->whatsAppService = $this->app->make(WhatsAppService::class);

        $this->user = User::firstOrFail();
        $organization = $this->user->organizations()->firstOrFail();
        $this->chatbot = $organization->chatbots()->firstOrFail();

        $whatsAppChannel = Channel::where('slug', 'whatsapp')->first();
        $this->channel = $this->chatbot->chatbotChannels()
            ->where('channel_id', $whatsAppChannel->id)
            ->first();
        $this->channel->update(
            [
                'credentials' => [
                    'whatsapp_business_account_id' => '102290129340398',
                    'whatsapp_business_access_token' => 'EAAJBsdgh',
                ],
                'webhook_url' => 'https://graph.facebook.com/v24.0/',
            ]
        );

        $this->marketingCategory = MessageTemplateCategory::where('slug', 'marketing')->firstOrFail();
    }

    #[Test]
    public function it_builds_the_whatsapp_api_payload_correctly_for_review_submission(): void
    {
        Http::fake([
            'https://graph.facebook.com/v24.0/*/message_templates' => Http::response([
                'id' => 'mocked_external_id_123',
                'status' => 'pending',
            ], 200),
        ]);

        // Create a MessageTemplate that matches the example data from WABA docs
        $template = MessageTemplate::factory()->for($this->channel)->create([
            'name' => 'seasonal_promotion',
            'language' => 'en_US',
            'category_id' => $this->marketingCategory->id,
            'header_type' => 'text',
            'header_content' => 'Our {{1}} is on!',
            'body_content' => 'Shop now through {{1}} and use code {{2}} to get {{3}} off of all merchandise.',
            'footer_content' => 'Use the buttons below to manage your marketing subscriptions',
            'button_config' => [
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from Promos'],
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from All'],
            ],
            'example_data' => [
                'header_text' => ['Summer Sale'],
                'body_text' => [
                    ['the end of August', '25OFF', '25%'],
                ],
            ],
            // Ensure status and platform_status match default expected by the service
            'status' => 'pending',
            'platform_status' => 1,
        ]);

        // Define the expected payload that WhatsApp API should receive
        $expectedPayload = [
            'name' => 'seasonal_promotion',
            'language' => 'en_US',
            'category' => 'MARKETING', // Category name
            'parameter_format' => 'positional',
            'components' => [
                [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => 'Our {{1}} is on!',
                    'example' => [
                        'header_text' => ['Summer Sale'],
                    ],
                ],
                [
                    'type' => 'BODY',
                    'text' => 'Shop now through {{1}} and use code {{2}} to get {{3}} off of all merchandise.',
                    'example' => [
                        'body_text' => [
                            ['the end of August', '25OFF', '25%'],
                        ],
                    ],
                ],
                [
                    'type' => 'FOOTER',
                    'text' => 'Use the buttons below to manage your marketing subscriptions',
                ],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        [
                            'type' => 'QUICK_REPLY',
                            'text' => 'Unsubscribe from Promos',
                        ],
                        [
                            'type' => 'QUICK_REPLY',
                            'text' => 'Unsubscribe from All',
                        ],
                    ],
                ],
            ],
        ];

        // Call the service method
        $this->whatsAppService->submitTemplateForReview($template);

        // Assert that an HTTP request was sent with the correct payload
        Http::assertSent(function (Request $request) use ($expectedPayload) {
            // Check the URL
            $expectedUrl = 'https://graph.facebook.com/v24.0/102290129340398/message_templates';
            $this->assertEquals($expectedUrl, $request->url());

            // Check the headers
            $this->assertStringContainsString('Bearer EAAJBsdgh', $request->header('Authorization')[0]);
            $this->assertEquals('application/json', $request->header('Content-Type')[0]);

            // Check the body payload - this is the core assertion
            $this->assertEquals($expectedPayload, $request->data());

            return true; // Indicate that this request matches our assertion criteria
        });
    }

    #[Test]
    public function it_handles_url_buttons_correctly_in_payload(): void
    {
        Http::fake([
            'https://graph.facebook.com/v24.0/*/message_templates' => Http::response([
                'id' => 'mocked_external_id_123',
                'status' => 'pending',
            ], 200),
        ]);

        $template = MessageTemplate::factory()->for($this->channel)->create([
            'name' => 'promo_link',
            'language' => 'en_US',
            'category_id' => $this->marketingCategory->id,
            'header_type' => 'none',
            'body_content' => 'Check out our new products!',
            'footer_content' => null,
            'button_config' => [
                ['type' => 'URL', 'text' => 'View Products', 'url' => 'https://example.com/products'],
            ],
            'example_data' => [
                'body_text' => [
                    ['John Doe'],
                ],
            ],
        ]);

        $expectedPayload = [
            'name' => 'promo_link',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'parameter_format' => 'positional',
            'components' => [
                [
                    'type' => 'BODY',
                    'text' => 'Check out our new products!',
                    'example' => [
                        'body_text' => [
                            ['John Doe'],
                        ],
                    ],
                ],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        [
                            'type' => 'URL',
                            'text' => 'View Products',
                            'url' => 'https://example.com/products',
                        ]
                    ],
                ],
            ],
        ];

        $this->whatsAppService->submitTemplateForReview($template);

        Http::assertSent(function (Request $request) use ($expectedPayload) {
            $this->assertEquals($expectedPayload, $request->data());

            return true;
        });
    }

    #[Test]
    public function it_handles_named_parameters_correctly_in_payload(): void
    {
        Http::fake([
            'https://graph.facebook.com/v24.0/*/message_templates' => Http::response([
                'id' => 'mocked_external_id_123',
                'status' => 'pending',
            ], 200),
        ]);

        $template = MessageTemplate::factory()->for($this->channel)->create([
            'name' => 'seasonal_promotion',
            'language' => 'en_US',
            'category_id' => $this->marketingCategory->id,
            'header_type' => 'text',
            'header_content' => 'Our {{campaign}} is on!',
            'body_content' => 'Shop now through {{store}} and use code {{promo_code}} to get {{discount}} off of all merchandise.',
            'footer_content' => 'Use the buttons below to manage your marketing subscriptions',
            'button_config' => [
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from Promos'],
                ['type' => 'QUICK_REPLY', 'text' => 'Unsubscribe from All'],
            ],
            'example_data' => [
                'header_text_named_params' => [
                    [
                        'param_name'    => 'campaign',
                        'example' => 'Summer Sale',
                    ]
                ],
                'body_text_named_params'   => [
                    [
                        'param_name' => 'store',
                        'example'    => 'the end of August',
                    ],
                    [
                        'param_name' => 'promo_code',
                        'example'    => '25OFF',
                    ],
                    [
                        'param_name' => 'discount',
                        'example'    => '25%',
                    ]
                ]
            ],
            'status' => 'pending',
            'platform_status' => 1,
        ]);

        // Define the expected payload that WhatsApp API should receive
        $expectedPayload = [
            'name' => 'seasonal_promotion',
            'language' => 'en_US',
            'category' => 'MARKETING',
            'parameter_format' => 'named',
            'components' => [
                [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => 'Our {{campaign}} is on!',
                    'example' => [
                        'header_text_named_params' => [
                            [
                                'param_name'    => 'campaign',
                                'example' => 'Summer Sale',
                            ]
                        ],
                    ]
                ],
                [
                    'type' => 'BODY',
                    'text' => 'Shop now through {{store}} and use code {{promo_code}} to get {{discount}} off of all merchandise.',
                    'example' => [
                        'body_text_named_params'   => [
                            [
                                'param_name' => 'store',
                                'example'    => 'the end of August',
                            ],
                            [
                                'param_name' => 'promo_code',
                                'example'    => '25OFF',
                            ],
                            [
                                'param_name' => 'discount',
                                'example'    => '25%',
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'FOOTER',
                    'text' => 'Use the buttons below to manage your marketing subscriptions',
                ],
                [
                    'type' => 'BUTTONS',
                    'buttons' => [
                        [
                            'type' => 'QUICK_REPLY',
                            'text' => 'Unsubscribe from Promos',
                        ],
                        [
                            'type' => 'QUICK_REPLY',
                            'text' => 'Unsubscribe from All',
                        ]
                    ]
                ]
            ]
        ];

        $this->whatsAppService->submitTemplateForReview($template);

        // Assert that an HTTP request was sent with the correct payload
        Http::assertSent(function (Request $request) use ($expectedPayload) {
            // Check the URL
            $expectedUrl = 'https://graph.facebook.com/v24.0/102290129340398/message_templates';
            $this->assertEquals($expectedUrl, $request->url());

            // Check the headers
            $this->assertStringContainsString('Bearer EAAJBsdgh', $request->header('Authorization')[0]);
            $this->assertEquals('application/json', $request->header('Content-Type')[0]);

            // Check the body payload - this is the core assertion
            $this->assertEquals($expectedPayload, $request->data());

            return true; // Indicate that this request matches our assertion criteria
        });
    }
}
