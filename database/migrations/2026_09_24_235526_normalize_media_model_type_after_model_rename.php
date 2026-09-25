<?php

use App\Support\DatabaseView;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The Eloquent models dropped their `Model` suffix (e.g. RehearsalModel -> Rehearsal,
     * PresentationModel -> Presentation). Because there is no morph map, `media.model_type`
     * stores the fully-qualified class name, so existing rows point at the old class names
     * (including the even older PracticeRunModel). Normalize them to the current names and
     * recreate the practice_run_history view, whose recording filter follows the rename.
     *
     * @var array<string, string>
     */
    private array $renames = [
        'App\\Models\\PracticeRunModel' => 'App\\Models\\Rehearsal',
        'App\\Models\\RehearsalModel' => 'App\\Models\\Rehearsal',
        'App\\Models\\PresentationModel' => 'App\\Models\\Presentation',
    ];

    public function up(): void
    {
        foreach ($this->renames as $old => $new) {
            DB::table('media')->where('model_type', $old)->update(['model_type' => $new]);
        }

        DB::statement('DROP VIEW IF EXISTS practice_run_history');
        DB::statement(DatabaseView::sql('2026_09_19_150000_practice_run_history.sql'));
    }

    public function down(): void
    {
        // PracticeRunModel history collapses into Rehearsal, so only the direct
        // suffix renames can be reversed unambiguously.
        DB::table('media')->where('model_type', 'App\\Models\\Rehearsal')->update(['model_type' => 'App\\Models\\RehearsalModel']);
        DB::table('media')->where('model_type', 'App\\Models\\Presentation')->update(['model_type' => 'App\\Models\\PresentationModel']);
    }
};
