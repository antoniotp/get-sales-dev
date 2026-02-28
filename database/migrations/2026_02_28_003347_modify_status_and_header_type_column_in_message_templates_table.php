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
            $table->string('status', 30)->change();
            $table->string('header_type', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'paused', 'disabled'])->change();
            $table->enum('header_type', ['none', 'text', 'image', 'video', 'document'])->change();
        });
    }
};
