<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'name' => 'WhatsApp Business',
                'slug' => 'whatsapp',
                'icon' => 'fab fa-whatsapp',
                'webhook_fields' => [
                    'verify_token' => [
                        'type' => 'string',
                        'label' => 'Verify Token',
                        'required' => true,
                        'description' => 'Token para verificar el webhook'
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL del webhook para recibir mensajes'
                    ]
                ],
                'credentials_fields' => [
                    'access_token' => [
                        'type' => 'password',
                        'label' => 'Access Token',
                        'required' => true,
                        'description' => 'Token de acceso de WhatsApp Business API'
                    ],
                    'phone_number_id' => [
                        'type' => 'string',
                        'label' => 'Phone Number ID',
                        'required' => true,
                        'description' => 'ID del número de teléfono de WhatsApp Business'
                    ],
                    'business_account_id' => [
                        'type' => 'string',
                        'label' => 'Business Account ID',
                        'required' => true,
                        'description' => 'ID de la cuenta de WhatsApp Business'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Facebook Messenger',
                'slug' => 'messenger',
                'icon' => 'fab fa-facebook-messenger',
                'webhook_fields' => [
                    'verify_token' => [
                        'type' => 'string',
                        'label' => 'Verify Token',
                        'required' => true,
                        'description' => 'Token para verificar el webhook'
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL del webhook para recibir mensajes'
                    ]
                ],
                'credentials_fields' => [
                    'page_access_token' => [
                        'type' => 'password',
                        'label' => 'Page Access Token',
                        'required' => true,
                        'description' => 'Token de acceso de la página de Facebook'
                    ],
                    'app_secret' => [
                        'type' => 'password',
                        'label' => 'App Secret',
                        'required' => true,
                        'description' => 'Secreto de la aplicación de Facebook'
                    ],
                    'page_id' => [
                        'type' => 'string',
                        'label' => 'Page ID',
                        'required' => true,
                        'description' => 'ID de la página de Facebook'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Instagram Direct',
                'slug' => 'instagram',
                'icon' => 'fab fa-instagram',
                'webhook_fields' => [
                    'verify_token' => [
                        'type' => 'string',
                        'label' => 'Verify Token',
                        'required' => true,
                        'description' => 'Token para verificar el webhook'
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL del webhook para recibir mensajes'
                    ]
                ],
                'credentials_fields' => [
                    'access_token' => [
                        'type' => 'password',
                        'label' => 'Access Token',
                        'required' => true,
                        'description' => 'Token de acceso de Instagram'
                    ],
                    'instagram_account_id' => [
                        'type' => 'string',
                        'label' => 'Instagram Account ID',
                        'required' => true,
                        'description' => 'ID de la cuenta de Instagram Business'
                    ],
                    'page_id' => [
                        'type' => 'string',
                        'label' => 'Connected Facebook Page ID',
                        'required' => true,
                        'description' => 'ID de la página de Facebook conectada'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Telegram Bot',
                'slug' => 'telegram',
                'icon' => 'fab fa-telegram-plane',
                'webhook_fields' => [
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL del webhook para recibir mensajes'
                    ]
                ],
                'credentials_fields' => [
                    'bot_token' => [
                        'type' => 'password',
                        'label' => 'Bot Token',
                        'required' => true,
                        'description' => 'Token del bot de Telegram obtenido de @BotFather'
                    ],
                    'bot_username' => [
                        'type' => 'string',
                        'label' => 'Bot Username',
                        'required' => true,
                        'description' => 'Nombre de usuario del bot (sin @)'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Discord Bot',
                'slug' => 'discord',
                'icon' => 'fab fa-discord',
                'webhook_fields' => [
                    'application_id' => [
                        'type' => 'string',
                        'label' => 'Application ID',
                        'required' => true,
                        'description' => 'ID de la aplicación de Discord'
                    ]
                ],
                'credentials_fields' => [
                    'bot_token' => [
                        'type' => 'password',
                        'label' => 'Bot Token',
                        'required' => true,
                        'description' => 'Token del bot de Discord'
                    ],
                    'application_id' => [
                        'type' => 'string',
                        'label' => 'Application ID',
                        'required' => true,
                        'description' => 'ID de la aplicación de Discord'
                    ],
                    'guild_id' => [
                        'type' => 'string',
                        'label' => 'Guild ID (Optional)',
                        'required' => false,
                        'description' => 'ID del servidor de Discord (opcional para comandos globales)'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Slack Bot',
                'slug' => 'slack',
                'icon' => 'fab fa-slack',
                'webhook_fields' => [
                    'signing_secret' => [
                        'type' => 'password',
                        'label' => 'Signing Secret',
                        'required' => true,
                        'description' => 'Secreto de firma para verificar requests de Slack'
                    ]
                ],
                'credentials_fields' => [
                    'bot_token' => [
                        'type' => 'password',
                        'label' => 'Bot User OAuth Token',
                        'required' => true,
                        'description' => 'Token OAuth del bot de Slack'
                    ],
                    'app_token' => [
                        'type' => 'password',
                        'label' => 'App-Level Token',
                        'required' => false,
                        'description' => 'Token a nivel de aplicación (para Socket Mode)'
                    ],
                    'team_id' => [
                        'type' => 'string',
                        'label' => 'Team ID',
                        'required' => true,
                        'description' => 'ID del workspace de Slack'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Microsoft Teams',
                'slug' => 'teams',
                'icon' => 'fas fa-users',
                'webhook_fields' => [
                    'app_id' => [
                        'type' => 'string',
                        'label' => 'App ID',
                        'required' => true,
                        'description' => 'ID de la aplicación de Microsoft Teams'
                    ]
                ],
                'credentials_fields' => [
                    'app_id' => [
                        'type' => 'string',
                        'label' => 'Microsoft App ID',
                        'required' => true,
                        'description' => 'ID de la aplicación de Microsoft'
                    ],
                    'app_password' => [
                        'type' => 'password',
                        'label' => 'Microsoft App Password',
                        'required' => true,
                        'description' => 'Contraseña de la aplicación de Microsoft'
                    ],
                    'tenant_id' => [
                        'type' => 'string',
                        'label' => 'Tenant ID',
                        'required' => false,
                        'description' => 'ID del tenant de Azure AD (opcional)'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Web Chat Widget',
                'slug' => 'webchat',
                'icon' => 'fas fa-comments',
                'webhook_fields' => [
                    'allowed_origins' => [
                        'type' => 'textarea',
                        'label' => 'Allowed Origins',
                        'required' => true,
                        'description' => 'Dominios permitidos para el widget (uno por línea)'
                    ]
                ],
                'credentials_fields' => [
                    'api_key' => [
                        'type' => 'password',
                        'label' => 'API Key',
                        'required' => true,
                        'description' => 'Clave API para autenticación del widget'
                    ],
                    'widget_theme' => [
                        'type' => 'select',
                        'label' => 'Widget Theme',
                        'required' => false,
                        'options' => ['light', 'dark', 'auto'],
                        'description' => 'Tema visual del widget de chat'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'Email',
                'slug' => 'email',
                'icon' => 'fas fa-envelope',
                'webhook_fields' => [
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL para recibir emails entrantes'
                    ]
                ],
                'credentials_fields' => [
                    'smtp_host' => [
                        'type' => 'string',
                        'label' => 'SMTP Host',
                        'required' => true,
                        'description' => 'Servidor SMTP para envío de emails'
                    ],
                    'smtp_port' => [
                        'type' => 'number',
                        'label' => 'SMTP Port',
                        'required' => true,
                        'description' => 'Puerto SMTP (25, 587, 465)'
                    ],
                    'smtp_username' => [
                        'type' => 'string',
                        'label' => 'SMTP Username',
                        'required' => true,
                        'description' => 'Usuario para autenticación SMTP'
                    ],
                    'smtp_password' => [
                        'type' => 'password',
                        'label' => 'SMTP Password',
                        'required' => true,
                        'description' => 'Contraseña para autenticación SMTP'
                    ],
                    'from_email' => [
                        'type' => 'email',
                        'label' => 'From Email',
                        'required' => true,
                        'description' => 'Email remitente por defecto'
                    ],
                    'from_name' => [
                        'type' => 'string',
                        'label' => 'From Name',
                        'required' => false,
                        'description' => 'Nombre del remitente por defecto'
                    ]
                ],
                'status' => 1
            ],
            [
                'name' => 'SMS (Twilio)',
                'slug' => 'sms-twilio',
                'icon' => 'fas fa-sms',
                'webhook_fields' => [
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'required' => true,
                        'description' => 'URL del webhook para recibir SMS'
                    ]
                ],
                'credentials_fields' => [
                    'account_sid' => [
                        'type' => 'string',
                        'label' => 'Account SID',
                        'required' => true,
                        'description' => 'SID de la cuenta de Twilio'
                    ],
                    'auth_token' => [
                        'type' => 'password',
                        'label' => 'Auth Token',
                        'required' => true,
                        'description' => 'Token de autenticación de Twilio'
                    ],
                    'phone_number' => [
                        'type' => 'string',
                        'label' => 'Twilio Phone Number',
                        'required' => true,
                        'description' => 'Número de teléfono de Twilio (formato: +1234567890)'
                    ]
                ],
                'status' => 1
            ]
        ];

        foreach ($channels as $channelData) {
            Channel::updateOrCreate(
                ['slug' => $channelData['slug']],
                $channelData
            );
        }

        $this->command->info('Channels seeded successfully!');
    }
}
