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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->boolean('verified_email')->default(false);
            $table->boolean('verified_phone')->default(false);
            $table->string('country_code', 2)->nullable()->comment('ISO 3166-1 alpha-2 (MX, US, etc.)');
            $table->string('language_code', 5)->nullable()->comment('ISO 639-1 (es, en-US, etc.)');
            $table->string('timezone', 50)->nullable()->comment('America/Mexico_City, UTC, etc.');
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index(['organization_id', 'email']);
            $table->index(['organization_id', 'phone_number']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};