<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2)->comment('ISO country code (US, MX, etc.)');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->comment('Currency code (USD, MXN, etc.)');
            $table->enum('billing_period', ['monthly', 'quarterly', 'semi-annually', 'annually'])->comment('Billing frequency for the subscription.');
            $table->timestamps();
            $table->softDeletes();

            // indexes
            $table->unique(['subscription_id', 'country_code', 'billing_period'], 'unique_plan_country_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_pricing');
    }
};
