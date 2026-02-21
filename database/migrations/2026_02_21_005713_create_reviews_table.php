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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('guide_id')->constrained('guides')->onDelete('cascade');
            $table->foreignId('tour_id')->nullable()->constrained('tours')->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('set null');
            $table->integer('rating'); // 1-5
            $table->text('review_text');
            $table->integer('helpful_count')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('moderated_by')->nullable(); // nullable as admin_users might not be explicitly linked
            $table->text('moderation_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
