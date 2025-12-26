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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->string('external_message_id')->nullable();
            $table->enum('type', ['incoming', 'outgoing']);
            $table->text('content');
            $table->enum('content_type', ['text', 'image', 'audio', 'video', 'document', 'location'])->default('text');
            $table->string('media_url', 500)->nullable();
            $table->enum('sender_type', ['contact', 'ai', 'human']);
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Índices
            $table->index('type');
            $table->index('sender_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
