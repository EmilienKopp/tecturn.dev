<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS talks_overview');
        DB::statement(DatabaseView::sql('2026_10_06_120002_talks_overview.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS talks_overview');
    }
};
