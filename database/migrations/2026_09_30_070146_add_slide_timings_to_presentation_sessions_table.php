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
        // Per-slide active seconds of the live run, tracked client-side like a
        // rehearsal's and delivered with the close beacon. Null for sessions
        // that predate this or whose close beacon never landed.
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->json('slide_timings')->nullable()->after('word_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->dropColumn('slide_timings');
        });
    }
};
