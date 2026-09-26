<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_beta_requests');
        DB::statement(DatabaseView::sql('2026_09_12_120000_admin_beta_requests.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_beta_requests');
    }
};
