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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_channel_id')->constrained()->onDelete('cascade');
            $table->string('external_conversation_id');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_avatar', 500)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->enum('mode', ['ai', 'human'])->default('ai');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('mode');
            $table->index('last_message_at');

            // Unique constraint
            $table->unique(['chatbot_channel_id', 'external_conversation_id'], 'unique_source_external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
