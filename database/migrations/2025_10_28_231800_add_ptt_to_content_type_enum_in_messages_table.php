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
        Schema::table('messages', function (Blueprint $table) {
            // Add 'ptt' to the existing ENUM values
            $table->enum('content_type', [
                'text',
                'image',
                'audio',
                'video',
                'document',
                'location',
                'pending',
                'ptt', // New value
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Revert to the original ENUM values
            $table->enum('content_type', [
                'text',
                'image',
                'audio',
                'video',
                'document',
                'location',
                'pending',
            ])->change();
        });
    }
};
