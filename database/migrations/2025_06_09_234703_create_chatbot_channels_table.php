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
        Schema::create('chatbot_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained();
            $table->string('name');
            $table->string('webhook_url', 500)->nullable();
            $table->json('credentials')->nullable();
            $table->json('webhook_config')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_channels');
    }
};
