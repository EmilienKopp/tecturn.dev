<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events');
        DB::statement(DatabaseView::sql('2026_10_06_120000_calendar_events.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events');
    }
};
