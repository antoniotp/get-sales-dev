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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable()->comment('Default price of the subscription plan.');
            $table->string('currency', 3)->nullable()->comment('Default currency code for the subscription price (e.g., USD, EUR, MXN).');
            $table->enum('billing_period', ['monthly', 'quarterly', 'semi-annually', 'annually'])->comment('Billing frequency for the subscription.');
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('max_chatbots')->nullable()->comment('Maximum number of chatbots allowed (NULL = unlimited)');
            $table->unsignedInteger('max_messages_per_month')->nullable()->comment('Monthly message limit (NULL = unlimited)');
            $table->json('features')->nullable()->comment('Additional features as JSON array');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
