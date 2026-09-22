<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // PracticeRunModel was renamed to RehearsalModel, so the media morph type
    // stored in `media.model_type` changed. The view name and columns are
    // unchanged; only the `model_type LIKE` filter follows the class rename.
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(file_get_contents(database_path('views/2026_09_19_150000_practice_run_history.sql')));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(file_get_contents(database_path('views/2026_09_18_000004_practice_run_history.sql')));
    }
};
