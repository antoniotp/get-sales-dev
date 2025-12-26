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
        $tablesWithSoftDeletes = [
            'invitations',
            'chatbot_channel_settings',
            'contact_entities',
            'message_template_sends',
            'organization_subscriptions',
            'public_form_links',
            'public_form_templates',
            'users',
        ];

        foreach ($tablesWithSoftDeletes as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('sent_at')->nullable()->after('metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesWithSoftDeletes = [
            'invitations',
            'chatbot_channel_settings',
            'contact_entities',
            'message_template_sends',
            'organization_subscriptions',
            'public_form_links',
            'public_form_templates',
            'users',
        ];

        foreach ($tablesWithSoftDeletes as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('sent_at');
        });
    }
};
