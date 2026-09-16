<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practice_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')->constrained('presentations')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at');
            // Active rehearsal time only — paused stretches are excluded.
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('slide_timings');
            // Frozen copy of the deck at rehearsal time, so a run replays the
            // slides as they were even after the presentation is edited.
            $table->json('content');
            $table->json('flow')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'started_at']);
            $table->index(['presentation_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_runs');
    }
};
