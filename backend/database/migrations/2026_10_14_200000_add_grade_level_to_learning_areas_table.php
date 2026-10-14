<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_areas', function (Blueprint $table) {
            // Existing records remain unassigned until their grade is known.
            $table->unsignedTinyInteger('grade_level')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('learning_areas', function (Blueprint $table) {
            $table->dropIndex(['grade_level']);
            $table->dropColumn('grade_level');
        });
    }
};
