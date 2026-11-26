<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('practice_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained()->restrictOnDelete();
            $table->foreignId('sub_strand_id')->constrained()->restrictOnDelete();
            $table->string('title', 160);
            $table->string('status')->default('draft');
            $table->json('questions');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['school_class_id', 'user_id', 'status']);
        });
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->foreignId('practice_exercise_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('exercise_title', 160)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropForeign(['practice_exercise_id']);
            $table->dropColumn(['practice_exercise_id', 'exercise_title']);
        });
        Schema::dropIfExists('practice_exercises');
    }
};
