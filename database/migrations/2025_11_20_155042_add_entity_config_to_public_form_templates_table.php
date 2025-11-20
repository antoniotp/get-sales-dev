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
        Schema::table('public_form_templates', function (Blueprint $table) {
            $table->json('entity_config')->nullable()->after('custom_fields_schema');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_form_templates', function (Blueprint $table) {
            $table->dropColumn('entity_config');
        });
    }
};
