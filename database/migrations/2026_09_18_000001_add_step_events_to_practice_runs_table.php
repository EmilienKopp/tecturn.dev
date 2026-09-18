<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            // Timeline of slide/step positions in active-time milliseconds,
            // aligned with the voice recording so replay can drive navigation.
            $table->json('step_events')->nullable()->after('slide_timings');
        });
    }

    public function down(): void
    {
        Schema::table('practice_runs', function (Blueprint $table) {
            $table->dropColumn('step_events');
        });
    }
};
