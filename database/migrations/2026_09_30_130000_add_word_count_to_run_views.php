<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Both run views gain the stored word_count so the delivery-stats read
    // model can average words per minute without re-counting deck text.
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(DatabaseView::sql('2026_09_30_130000_practice_run_history.sql'));

        DB::statement('DROP VIEW IF EXISTS session_analytics');
        DB::statement(DatabaseView::sql('2026_09_30_130000_session_analytics.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(DatabaseView::sql('2026_09_19_150000_practice_run_history.sql'));

        DB::statement('DROP VIEW IF EXISTS session_analytics');
        DB::statement(DatabaseView::sql('2026_09_03_234736_session_analytics.sql'));
    }
};
