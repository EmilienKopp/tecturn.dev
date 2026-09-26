<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_feedback');
        DB::statement(DatabaseView::sql('2026_09_26_120100_admin_feedback.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_feedback');
    }
};
