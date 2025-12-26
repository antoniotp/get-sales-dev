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
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type')->default('direct')->index()->after('chatbot_channel_id');
            $table->string('name')->nullable()->after('type');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_contact_id')->nullable()->after('sender_user_id');
            $table->foreign('sender_contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_type_index');
            $table->dropColumn('type');
            $table->dropColumn('name');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['sender_contact_id']);
            $table->dropColumn('sender_contact_id');
        });
    }
};
