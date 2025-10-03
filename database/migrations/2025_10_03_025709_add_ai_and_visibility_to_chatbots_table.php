<?php

use App\Enums\Chatbot\AgentVisibility;
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
        Schema::table('chatbots', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(false)->after('status');
            $table->string('agent_visibility')->default(AgentVisibility::ALL->value)->after('ai_enabled'); // all, assigned_only
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chatbots', function (Blueprint $table) {
            $table->dropColumn('agent_visibility');
            $table->dropColumn('ai_enabled');
        });
    }
};