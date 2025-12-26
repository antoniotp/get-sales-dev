<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\SubscriptionPricing;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptions = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Basic features for getting started',
                'price' => 0.00,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'is_free' => true,
                'max_chatbots' => 1,
                'max_messages_per_month' => 100,
                'features' => ['basic_chatbot', 'whatsapp_integration'],
                'status' => 1,
                'pricing' => [
                    ['country_code' => 'US', 'price' => 0.00, 'currency' => 'USD'],
                    ['country_code' => 'MX', 'price' => 0.00, 'currency' => 'MXN'],
                ]
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses',
                'price' => 29.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'is_free' => false,
                'max_chatbots' => 3,
                'max_messages_per_month' => 1000,
                'features' => ['basic_chatbot', 'whatsapp_integration', 'messenger_integration', 'basic_analytics'],
                'status' => 1,
                'pricing' => [
                    ['country_code' => 'US', 'price' => 29.99, 'currency' => 'USD'],
                    ['country_code' => 'MX', 'price' => 599.00, 'currency' => 'MXN'],
                ]
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Advanced features for growing businesses',
                'price' => 79.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'is_free' => false,
                'max_chatbots' => 10,
                'max_messages_per_month' => 5000,
                'features' => [
                    'advanced_chatbot',
                    'all_integrations',
                    'advanced_analytics',
                    'custom_branding',
                    'priority_support'
                ],
                'status' => 1,
                'pricing' => [
                    ['country_code' => 'US', 'price' => 79.99, 'currency' => 'USD'],
                    ['country_code' => 'MX', 'price' => 1599.00, 'currency' => 'MXN'],
                ]
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited features for large organizations',
                'price' => 199.99,
                'currency' => 'USD',
                'billing_period' => 'monthly',
                'is_free' => false,
                'max_chatbots' => null, // Unlimited
                'max_messages_per_month' => null, // Unlimited
                'features' => [
                    'enterprise_chatbot',
                    'all_integrations',
                    'enterprise_analytics',
                    'white_label',
                    'dedicated_support',
                    'custom_integrations',
                    'sla_guarantee'
                ],
                'status' => 1,
                'pricing' => [
                    ['country_code' => 'US', 'price' => 199.99, 'currency' => 'USD'],
                    ['country_code' => 'MX', 'price' => 3999.00, 'currency' => 'MXN'],
                ]
            ],
        ];

        foreach ($subscriptions as $subscriptionData) {
            $pricing = $subscriptionData['pricing'];
            unset($subscriptionData['pricing']);

            $subscription = Subscription::firstOrCreate(
                ['slug' => $subscriptionData['slug']],
                $subscriptionData
            );

            // Create pricing for different billing periods
            $billingPeriods = ['monthly', 'quarterly', 'semi-annually', 'annually'];
            $discounts = [
                'monthly' => 1.0,
                'quarterly' => 0.95, // 5% discount
                'semi-annually' => 0.90, // 10% discount
                'annually' => 0.83, // 17% discount (2 months free)
            ];

            foreach ($pricing as $priceData) {
                foreach ($billingPeriods as $period) {
                    $basePrice = $priceData['price'];
                    $discount = $discounts[$period];
                    $periodMultiplier = match($period) {
                        'monthly' => 1,
                        'quarterly' => 3,
                        'semi-annually' => 6,
                        'annually' => 12,
                    };

                    $finalPrice = $basePrice * $periodMultiplier * $discount;

                    SubscriptionPricing::firstOrCreate(
                        [
                            'subscription_id' => $subscription->id,
                            'country_code' => $priceData['country_code'],
                            'billing_period' => $period,
                        ],
                        [
                            'price' => $finalPrice,
                            'currency' => $priceData['currency'],
                        ]
                    );
                }
            }
        }
    }
}
