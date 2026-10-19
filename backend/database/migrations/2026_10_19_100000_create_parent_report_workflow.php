<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->json('sub_strand_ids');
            $table->timestamp('finalised_at');
            $table->timestamps();
            $table->unique(['student_id', 'user_id', 'term']);
        });
        Schema::table('parent_access', function (Blueprint $table) {
            $table->foreignId('term_report_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('recipient_hash', 64)->nullable();
            $table->string('sms_status')->default('not_queued');
            $table->unsignedTinyInteger('sms_attempts')->default(0);
            $table->timestamp('sms_accepted_at')->nullable();
            $table->index(['student_id', 'term', 'is_active']);
        });
        Schema::create('parent_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_access_id')->constrained('parent_access')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_sessions');
        Schema::table('parent_access', function (Blueprint $table) {
            $table->dropConstrainedForeignId('term_report_id');
            $table->dropIndex(['student_id', 'term', 'is_active']);
            $table->dropColumn(['recipient_hash', 'sms_status', 'sms_attempts', 'sms_accepted_at']);
        });
        Schema::dropIfExists('term_reports');
    }
};
