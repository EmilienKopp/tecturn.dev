<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS presentations_view');
        DB::statement($this->readView('2026_09_26_000001_presentations.sql'));
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS presentations_view');
        DB::statement($this->readView('2026_09_19_140900_presentations.sql'));
    }

    private function readView(string $file): string
    {
        $sql = file_get_contents(database_path("views/{$file}"));

        if ($sql === false) {
            throw new RuntimeException("Unable to read view file: {$file}");
        }

        return $sql;
    }
};
