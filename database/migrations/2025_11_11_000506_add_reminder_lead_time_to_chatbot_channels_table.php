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
        Schema::table('chatbot_channels', function (Blueprint $table) {
            $table->integer('reminder_lead_time_minutes')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbot_channels', function (Blueprint $table) {
            $table->dropColumn('reminder_lead_time_minutes');
        });
    }
};
