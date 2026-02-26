<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_languages', function (Blueprint $table) {
            $table->boolean('is_principal')->default(false)->after('language_id');
        });
    }

    public function down(): void
    {
        Schema::table('guide_languages', function (Blueprint $table) {
            $table->dropColumn('is_principal');
        });
    }
};
