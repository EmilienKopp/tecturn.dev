<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(file_get_contents(database_path('views/2026_09_12_112624_admin_overview.sql')));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_overview');
    }
};
