<?php

namespace Database\Seeders;

use App\Models\MessageTemplateCategory;
use Illuminate\Database\Seeder;

class MessageTemplateCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Promotional and marketing messages',
                'color' => '#FF6B35',
                'icon' => 'megaphone',
                'is_platform_standard' => true,
                'sort_order' => 10,
                'status' => 1,
            ],
            [
                'name' => 'Utility',
                'slug' => 'utility',
                'description' => 'Transactional and utility messages',
                'color' => '#4A90E2',
                'icon' => 'tool',
                'is_platform_standard' => true,
                'sort_order' => 20,
                'status' => 1,
            ],
            [
                'name' => 'Authentication',
                'slug' => 'authentication',
                'description' => 'OTP and verification messages',
                'color' => '#27AE60',
                'icon' => 'shield-check',
                'is_platform_standard' => true,
                'sort_order' => 30,
                'status' => 1,
            ],
            [
                'name' => 'Promotional',
                'slug' => 'promotional',
                'description' => 'Promotional offers and deals',
                'color' => '#F39C12',
                'icon' => 'tag',
                'is_platform_standard' => true,
                'sort_order' => 40,
                'status' => 1,
            ],
            [
                'name' => 'Transactional',
                'slug' => 'transactional',
                'description' => 'Order confirmations, invoices, receipts',
                'color' => '#3498DB',
                'icon' => 'receipt',
                'is_platform_standard' => false,
                'sort_order' => 50,
                'status' => 1,
            ],
            [
                'name' => 'Support',
                'slug' => 'support',
                'description' => 'Customer support and FAQ',
                'color' => '#9B59B6',
                'icon' => 'help-circle',
                'is_platform_standard' => false,
                'sort_order' => 60,
                'status' => 1,
            ],
            [
                'name' => 'Notification',
                'slug' => 'notification',
                'description' => 'System notifications and alerts',
                'color' => '#E74C3C',
                'icon' => 'bell',
                'is_platform_standard' => false,
                'sort_order' => 70,
                'status' => 1,
            ],
            [
                'name' => 'Appointment',
                'slug' => 'appointment',
                'description' => 'Appointments and reminders',
                'color' => '#1ABC9C',
                'icon' => 'calendar',
                'is_platform_standard' => false,
                'sort_order' => 80,
                'status' => 1,
            ],
            [
                'name' => 'Shipping',
                'slug' => 'shipping',
                'description' => 'Shipping updates and tracking',
                'color' => '#F1C40F',
                'icon' => 'truck',
                'is_platform_standard' => false,
                'sort_order' => 90,
                'status' => 1,
            ],
            [
                'name' => 'Payment',
                'slug' => 'payment',
                'description' => 'Payment reminders and confirmations',
                'color' => '#2ECC71',
                'icon' => 'credit-card',
                'is_platform_standard' => false,
                'sort_order' => 100,
                'status' => 1,
            ],
            [
                'name' => 'Welcome',
                'slug' => 'welcome',
                'description' => 'Welcome and onboarding messages',
                'color' => '#E91E63',
                'icon' => 'heart',
                'is_platform_standard' => false,
                'sort_order' => 110,
                'status' => 1,
            ],
            [
                'name' => 'Survey',
                'slug' => 'survey',
                'description' => 'Surveys and feedback collection',
                'color' => '#673AB7',
                'icon' => 'clipboard-list',
                'is_platform_standard' => false,
                'sort_order' => 120,
                'status' => 1,
            ],
            [
                'name' => 'Event',
                'slug' => 'event',
                'description' => 'Events and invitations',
                'color' => '#FF5722',
                'icon' => 'calendar-days',
                'is_platform_standard' => false,
                'sort_order' => 130,
                'status' => 1,
            ],
            [
                'name' => 'Educational',
                'slug' => 'educational',
                'description' => 'Educational and tutorial content',
                'color' => '#795548',
                'icon' => 'graduation-cap',
                'is_platform_standard' => false,
                'sort_order' => 140,
                'status' => 1,
            ],
            [
                'name' => 'Seasonal',
                'slug' => 'seasonal',
                'description' => 'Seasonal promotions and holidays',
                'color' => '#607D8B',
                'icon' => 'sparkles',
                'is_platform_standard' => false,
                'sort_order' => 150,
                'status' => 1,
            ],
        ];

        foreach ($categories as $category) {
            MessageTemplateCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Message template categories seeded successfully!');
    }
}
