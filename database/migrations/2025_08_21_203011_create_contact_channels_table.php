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
        Schema::create('contact_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained()->onDelete('set null');
            $table->string('channel_identifier')->comment('phone_number, user_id, etc.');
            $table->json('channel_data')->nullable()->comment('Extra data from channel');
            $table->boolean('is_primary')->default(false)->comment('Primary contact channel for notifications and proactive communication');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['chatbot_id', 'channel_identifier', 'deleted_at'], 'contact_channels_unique');
            $table->index('contact_id');
            $table->index('chatbot_id');
            $table->index('channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_channels');
    }
};