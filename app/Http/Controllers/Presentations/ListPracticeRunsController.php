<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\PracticeRunReadModel;
use App\Infrastructure\ReadModels\RehearsalReviewReadModel;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListPracticeRunsController extends Controller
{
    public function __construct(
        private readonly PracticeRunReadModel $practiceRuns,
        private readonly RehearsalReviewReadModel $reviews,
    ) {}

    public function __invoke(Request $request, Team $current_team): Response
    {
        return Inertia::render('rehearsals/Index', [
            'runs' => $this->practiceRuns->listForTeam($current_team->id),
            'reviewRequests' => $this->reviews->listForReviewer($request->user()->id),
        ]);
    }
}
