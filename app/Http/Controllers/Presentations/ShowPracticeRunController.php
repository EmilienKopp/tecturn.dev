<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\PracticeRunReadModel;
use App\Models\PracticeRunModel;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class ShowPracticeRunController extends Controller
{
    public function __construct(private readonly PracticeRunReadModel $practiceRuns) {}

    public function __invoke(Team $current_team, PracticeRunModel $practice_run): Response
    {
        return Inertia::render('rehearsals/Show', [
            'run' => $this->practiceRuns->findForReplay($practice_run->id),
        ]);
    }
}
