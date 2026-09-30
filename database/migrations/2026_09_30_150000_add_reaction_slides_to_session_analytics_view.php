<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The session view gains the per-slide reaction tallies so the session
    // detail page can show which slides landed.
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS session_analytics');
        DB::statement(DatabaseView::sql('2026_09_30_150000_session_analytics.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS session_analytics');
        DB::statement(DatabaseView::sql('2026_09_30_140000_session_analytics.sql'));
    }
};
