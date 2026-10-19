<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->enum('event', ['baseline', 'created', 'updated']);
            $table->enum('competency_level', ['EE', 'ME', 'AE', 'BE']);
            $table->text('remarks')->nullable();
            $table->timestamp('recorded_at');
            $table->unique(['assessment_id', 'version']);
        });

        // Older edits cannot be reconstructed: record only the state observed at migration.
        DB::table('assessments')->orderBy('id')->chunkById(200, function ($records) {
            foreach ($records as $record) {
                DB::table('assessment_revisions')->insert([
                    'assessment_id' => $record->id, 'user_id' => $record->user_id,
                    'version' => 1, 'event' => 'baseline', 'competency_level' => $record->competency_level,
                    'remarks' => $record->remarks, 'recorded_at' => now(),
                ]);
            }
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared("CREATE FUNCTION cbc_prevent_revision_changes() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Assessment revisions are append-only'; END; $$ LANGUAGE plpgsql");
            DB::unprepared('CREATE TRIGGER assessment_revisions_immutable BEFORE UPDATE OR DELETE ON assessment_revisions FOR EACH ROW EXECUTE FUNCTION cbc_prevent_revision_changes()');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::unprepared("CREATE TRIGGER assessment_revisions_no_update BEFORE UPDATE ON assessment_revisions BEGIN SELECT RAISE(ABORT, 'Assessment revisions are append-only'); END");
            DB::unprepared("CREATE TRIGGER assessment_revisions_no_delete BEFORE DELETE ON assessment_revisions BEGIN SELECT RAISE(ABORT, 'Assessment revisions are append-only'); END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_revisions');
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS cbc_prevent_revision_changes()');
        }
    }
};
