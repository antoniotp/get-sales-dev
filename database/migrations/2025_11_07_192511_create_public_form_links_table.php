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
        Schema::create('public_form_links', function (Blueprint $table) {
            $table->uuid('uuid')->primary(); // Using UUID as primary key
            $table->foreignId('public_form_template_id')->constrained()->onDelete('cascade');
            $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->string('success_message')->nullable(); // Message to display after successful submission
            $table->text('redirect_on_success')->nullable(); // URL to redirect after successful submission
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_form_links');
    }
};
