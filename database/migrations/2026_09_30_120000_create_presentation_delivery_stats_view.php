<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(DatabaseView::sql('2026_09_30_120000_presentation_delivery_stats.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS presentation_delivery_stats');
    }
};
