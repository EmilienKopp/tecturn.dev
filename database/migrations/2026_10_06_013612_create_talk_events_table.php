<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guarded because a prior partial run may have left the table behind.
        if (! Schema::hasTable('talk_events')) {
            Schema::create('talk_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                // The talk being given at this occasion. Optional so a date can
                // be blocked out before the talk itself exists.
                $table->foreignId('talk_id')->nullable()->constrained('talks')->nullOnDelete();
                $table->string('name');
                $table->date('date');
                $table->time('start_time')->nullable();
                $table->timestamps();

                $table->index(['team_id', 'date']);
            });
        }

        // Guarded because a prior partial run may have left the column behind.
        if (! Schema::hasColumn('presentation_sessions', 'talk_event_id')) {
            Schema::table('presentation_sessions', function (Blueprint $table) {
                // Deliberately no FK constraint: adding one to an existing table
                // rewrites it on sqlite, which fails while DB views reference it.
                // EloquentTalkEventRepository::deleteById() nulls these instead.
                $table->unsignedBigInteger('talk_event_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::table('presentation_sessions', function (Blueprint $table) {
            $table->dropColumn('talk_event_id');
        });

        Schema::dropIfExists('talk_events');
    }
};
