<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(DatabaseView::sql('2026_10_06_120006_practice_run_history.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(DatabaseView::sql('2026_09_30_130000_practice_run_history.sql'));
    }
};
