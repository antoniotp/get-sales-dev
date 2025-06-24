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
        Schema::create('message_template_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('message_id')->nullable()->constrained()->onDelete('set null')->comment('Reference to the actual message sent (if available)');
            $table->json('variables_data')->nullable()->comment('Actual values used for template variables as JSON');
            $table->text('rendered_content')->nullable()->comment('Final rendered message content with variables replaced');
            $table->string('platform_message_id')->nullable()->comment('Message ID returned by the messaging platform');
            $table->enum('send_status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending')->comment('Status of the template message');
            $table->string('error_code', 50)->nullable()->comment('Error code if send failed');
            $table->text('error_message')->nullable()->comment('Detailed error message if send failed');
            $table->timestamp('sent_at')->nullable()->comment('When the message was successfully sent');
            $table->timestamp('delivered_at')->nullable()->comment('When the message was delivered (if supported by platform)');
            $table->timestamp('read_at')->nullable()->comment('When the message was read (if supported by platform)');
            $table->timestamp('failed_at')->nullable()->comment('When the send attempt failed');
            $table->timestamps();

            // Índices
            $table->index('send_status');
            $table->index('sent_at');
            $table->index('platform_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_template_sends');
    }
};
