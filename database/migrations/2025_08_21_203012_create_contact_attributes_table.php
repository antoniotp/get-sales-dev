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
        Schema::create('contact_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('attribute_name');
            $table->text('attribute_value')->nullable();
            $table->enum('source', ['conversation', 'manual', 'api', 'import'])->default('conversation');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contact_id', 'attribute_name', 'deleted_at'], 'contact_attributes_unique');
            $table->index('contact_id');
            $table->index('attribute_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_attributes');
    }
};