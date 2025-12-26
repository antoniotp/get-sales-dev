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
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('system_prompt')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedInteger('response_delay_min')->default(1);
            $table->unsignedInteger('response_delay_max')->default(5);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbots');
    }
};
