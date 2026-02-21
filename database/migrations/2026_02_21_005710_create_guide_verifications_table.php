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
        Schema::create('guide_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_id')->constrained('guides')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('admin_users')->onDelete('cascade');
            $table->boolean('step_1_account_verified')->default(false);
            $table->boolean('step_2_professional_info_verified')->default(false);
            $table->boolean('step_3_public_profile_verified')->default(false);
            $table->enum('overall_decision', ['pending', 'approved', 'rejected', 'revision_required'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_verifications');
    }
};
