<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS contact_profiles');
        DB::statement(DatabaseView::sql('2026_09_17_150304_contact_profiles.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS contact_profiles');
    }
};
