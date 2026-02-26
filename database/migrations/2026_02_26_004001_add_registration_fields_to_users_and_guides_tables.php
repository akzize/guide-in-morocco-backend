<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // phone is already present in users table
        });

        Schema::table('guides', function (Blueprint $table) {
            $table->string('whatsapp')->nullable();
            $table->string('agrement_number')->nullable();
            $table->date('agrement_date')->nullable();
            $table->string('agrement_authority')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'agrement_number', 'agrement_date', 'agrement_authority']);
        });
    }
};
