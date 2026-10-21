<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->foreignId('term_report_id')->nullable()->constrained()->nullOnDelete();
            $table->json('question_set')->nullable();
        });
        Schema::table('quiz_responses', function (Blueprint $table) {
            $table->unsignedTinyInteger('question_id')->nullable();
            $table->unsignedTinyInteger('selected_index')->nullable();
            $table->unique(['quiz_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_responses', function (Blueprint $table) {
            $table->dropUnique(['quiz_session_id', 'question_id']);
            $table->dropColumn(['question_id', 'selected_index']);
        });
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_report_id');
            $table->dropColumn('question_set');
        });
    }
};
