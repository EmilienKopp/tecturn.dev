<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // dbview:regen may already have created the view on this connection.
        DB::statement('DROP VIEW IF EXISTS review_comment_details');
        DB::statement(DatabaseView::sql('2026_09_18_000006_review_comment_details.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS review_comment_details');
    }
};
