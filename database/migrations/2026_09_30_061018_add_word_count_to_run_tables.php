<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Narration units (words + CJK chars) of the deck at run time, counted
        // client-side by lint.ts — the canonical counter — and stored so the
        // dashboard can average words-per-minute without re-counting. Null on
        // runs recorded before this landed; those simply skip the average.
        Schema::table('practice_runs', function (Blueprint $table) {
            $table->unsignedInteger('word_count')->nullable()->after('duration_seconds');
        });

        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->unsignedInteger('word_count')->nullable()->after('viewer_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            $table->dropColumn('word_count');
        });

        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->dropColumn('word_count');
        });
    }
};
