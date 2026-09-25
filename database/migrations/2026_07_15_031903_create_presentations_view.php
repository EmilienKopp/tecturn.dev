<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(DatabaseView::sql('2026_07_15_031903_presentations.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS presentations_view');
    }
};
