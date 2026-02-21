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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->foreignId('city_id')->constrained('cities')->onDelete('restrict');
            $table->foreignId('tour_type_id')->constrained('tour_types')->onDelete('restrict');
            $table->foreignId('difficulty_level_id')->constrained('difficulty_levels')->onDelete('restrict');
            $table->decimal('duration_in_hours', 5, 2);
            $table->string('duration_formatted');
            $table->decimal('price', 10, 2);
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->integer('max_persons');
            $table->integer('min_persons')->default(1);
            $table->string('featured_image_url')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_bookings')->default(0);
            $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
