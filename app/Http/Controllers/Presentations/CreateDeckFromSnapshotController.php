<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\CreateDeckFromSnapshot;
use App\Application\Commands\CreateDeckFromSnapshotCommand;
use App\Http\Controllers\Controller;
use App\Models\Rehearsal;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class CreateDeckFromSnapshotController extends Controller
{
    public function __construct(
        private readonly CreateDeckFromSnapshot $createDeckFromSnapshot,
    ) {}

    public function __invoke(Team $current_team, Rehearsal $rehearsal): RedirectResponse
    {
        $deck = $this->createDeckFromSnapshot->execute(
            new CreateDeckFromSnapshotCommand(rehearsalId: $rehearsal->id),
        );

        return redirect()->route('presentations.edit', [
            'current_team' => $current_team->slug,
            'presentation' => $deck->id,
        ]);
    }
}
