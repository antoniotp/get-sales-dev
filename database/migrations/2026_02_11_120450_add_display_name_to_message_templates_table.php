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
        Schema::table('message_templates', function (Blueprint $table) {
            $table->string('display_name')->after('name')->nullable()->comment('User-facing friendly name for the template.');
            $table->json('example_data')->after('variables_schema')->nullable()->comment('JSON object containing example values for variables, as required by Meta API.');
            $table->dropColumn('variables_schema');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'example_data']);
            $table->json('variables_schema')->after('variables_count')->nullable()->comment('Schema describing each variable (name, type, description) as JSON');
        });
    }
};
