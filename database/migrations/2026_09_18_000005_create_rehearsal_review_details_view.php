<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // dbview:regen may already have created the view on this connection.
        DB::statement('DROP VIEW IF EXISTS rehearsal_review_details');
        DB::statement(file_get_contents(database_path('views/2026_09_18_000005_rehearsal_review_details.sql')));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS rehearsal_review_details');
    }
};
