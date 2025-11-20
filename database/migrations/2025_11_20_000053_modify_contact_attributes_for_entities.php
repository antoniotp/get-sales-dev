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
        Schema::table('contact_attributes', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropIndex('contact_attributes_contact_id_index');

            $table->dropUnique('contact_attributes_unique');

            $table->dropColumn('contact_id');

            $table->foreignId('contact_entity_id')->after('id')->constrained()->onDelete('cascade');

            $table->unique(['contact_entity_id', 'attribute_name', 'deleted_at'], 'contact_entity_attributes_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_attributes', function (Blueprint $table) {
            $table->dropUnique('contact_entity_attributes_unique');
            $table->dropForeign(['contact_entity_id']);
            $table->dropColumn('contact_entity_id');

            // Recreate the original column and its constraints/indexes.
            $table->foreignId('contact_id')->after('id')->constrained()->onDelete('cascade');
            $table->unique(['contact_id', 'attribute_name', 'deleted_at'], 'contact_attributes_unique');

            $table->index('contact_id', 'contact_attributes_contact_id_index');
        });
    }
};
