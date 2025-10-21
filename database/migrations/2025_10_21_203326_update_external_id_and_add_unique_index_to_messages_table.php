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
        // Update existing messages with empty external_message_id to null
        // to prevent conflicts with the unique index
        DB::table('messages')
            ->where('external_message_id', '')
            ->update(['external_message_id' => null]);

        Schema::table('messages', function (Blueprint $table) {
            $table->string('external_message_id')->nullable()->change();
            $table->unique(['conversation_id', 'external_message_id'], 'messages_external_message_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique('messages_external_message_id_unique');
            $table->string('external_message_id')->nullable(false)->change();
        });
    }
};
