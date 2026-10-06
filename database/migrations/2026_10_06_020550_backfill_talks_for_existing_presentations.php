<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Every deck belongs to a talk: existing decks each get their own talk
     * (titled after the deck) as version 1.0. New decks are stamped at
     * creation by EloquentPresentationRepository.
     */
    public function up(): void
    {
        $now = now();

        DB::table('presentations')
            ->whereNull('talk_id')
            ->orderBy('id')
            ->chunkById(100, function ($presentations) use ($now) {
                foreach ($presentations as $presentation) {
                    $talkId = DB::table('talks')->insertGetId([
                        'team_id' => $presentation->team_id,
                        'title' => $presentation->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('presentations')
                        ->where('id', $presentation->id)
                        ->update([
                            'talk_id' => $talkId,
                            'version_major' => 1,
                            'version_minor' => 0,
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible data backfill: the created talks cannot be told apart
        // from user-created ones afterwards. Forward-fix instead.
    }
};
