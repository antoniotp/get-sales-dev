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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->foreignId('chatbot_channel_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('appointment_at');
            $table->string('status')->default('scheduled'); // e.g., scheduled, reminded, completed, cancelled
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
