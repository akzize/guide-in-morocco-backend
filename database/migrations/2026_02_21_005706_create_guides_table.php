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
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('location')->nullable();
            $table->text('bio')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->enum('certificate_status', ['pending', 'approved', 'rejected', 'revision_needed'])->default('pending');
            $table->integer('years_experience')->default(0);
            $table->decimal('hourly_rate_from', 8, 2)->nullable();
            $table->string('cover_image_url')->nullable();
            $table->timestamp('verified_at')->nullable();
            // We use integer for 'verified_by' because admin_users might be created after. We can add a foreign key constraint later if needed, or just nullable unsigned big integer.
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->decimal('average_rating_cache', 3, 2)->default(0);
            $table->boolean('popular_flag')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
