<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->boolean('is_main')->default(false);
            $table->timestamps();
            
            $table->unique(['guide_id', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_cities');
    }
};
