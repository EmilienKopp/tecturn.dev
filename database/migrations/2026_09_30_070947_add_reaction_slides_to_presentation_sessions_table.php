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
        // Which slide each audience reaction landed on, tallied by the
        // presenter screen (the one client that knows the current slide) and
        // delivered with the close beacon. Stored as a list of
        // {slide, reactions: {emoji: count}} so slide indexes survive JSON
        // round-trips regardless of sparseness.
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->json('reaction_slides')->nullable()->after('slide_timings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->dropColumn('reaction_slides');
        });
    }
};
