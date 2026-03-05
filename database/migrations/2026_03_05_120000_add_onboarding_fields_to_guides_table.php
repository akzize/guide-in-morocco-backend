<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('guides', function (Blueprint $table) {
			$table->string('guide_type')->nullable()->after('agrement_authority');
			$table->text('professional_experience')->nullable()->after('bio');
		});
	}

	public function down(): void
	{
		Schema::table('guides', function (Blueprint $table) {
			$table->dropColumn(['guide_type', 'professional_experience']);
		});
	}
};
