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
        Schema::create('message_template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('color', 7)->nullable()->comment('Color hex para UI (#FF5733)');
            $table->string('icon', 50)->nullable()->comment('Nombre del icono para UI');
            $table->boolean('is_platform_standard')->default(false)->comment('1 if required by messaging platforms like WhatsApp');
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            // Índices
            $table->index('status');
            $table->index('sort_order');
            $table->index('is_platform_standard');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_template_categories');
    }
};
