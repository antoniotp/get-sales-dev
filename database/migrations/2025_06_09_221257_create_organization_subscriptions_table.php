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
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->nullable()->comment('When subscription expires (NULL for lifetime)');
            $table->timestamp('cancelled_at')->nullable()->comment('When subscription was cancelled');
            $table->timestamps();

            // indexes
            $table->index('status', 'idx_organization_subscriptions_status');
            $table->index('expires_at', 'idx_organization_subscriptions_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_subscriptions');
    }
};
