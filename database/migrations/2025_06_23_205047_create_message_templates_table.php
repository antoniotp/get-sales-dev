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
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_channel_id')->constrained()->onDelete('cascade');
            $table->string('name')->comment('Template name for identification');
            $table->string('external_template_id')->nullable()->comment('ID provided by the messaging platform (WhatsApp, Meta, etc.)');
            $table->foreignId('category_id')->constrained('message_template_categories')->comment('Reference to message_template_categories table');
            $table->string('language', 10)->default('es')->comment('Language code (es, en, pt, etc.)');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paused', 'disabled'])->default('pending')->comment('Approval status from messaging platform');
            $table->tinyInteger('platform_status')->default(1)->comment('Internal status (1=active, 0=inactive)');
            $table->enum('header_type', ['none', 'text', 'image', 'video', 'document'])->default('none')->comment('Type of header content');
            $table->text('header_content')->nullable()->comment('Header text or media URL');
            $table->text('body_content')->comment('Main message body with variable placeholders like {{1}}, {{2}}');
            $table->text('footer_content')->nullable()->comment('Optional footer text');
            $table->json('button_config')->nullable()->comment('Button configuration as JSON array');
            $table->unsignedTinyInteger('variables_count')->default(0)->comment('Number of variables in the template ({{1}}, {{2}}, etc.)');
            $table->json('variables_schema')->nullable()->comment('Schema describing each variable (name, type, description) as JSON');
            $table->unsignedInteger('usage_count')->default(0)->comment('How many times this template has been used');
            $table->timestamp('last_used_at')->nullable()->comment('When this template was last used');
            $table->timestamp('approved_at')->nullable()->comment('When template was approved by platform');
            $table->text('rejected_reason')->nullable()->comment('Reason for rejection if status is rejected');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('platform_status');
            $table->index('language');
            $table->index('external_template_id');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
