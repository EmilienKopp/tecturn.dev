<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS contact_talks');
        DB::statement(file_get_contents(database_path('views/2026_09_17_150306_contact_talks.sql')));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS contact_talks');
    }
};
