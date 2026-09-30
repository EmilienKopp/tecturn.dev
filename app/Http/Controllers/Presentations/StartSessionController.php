<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\StartSession;
use App\Application\Commands\StartSessionCommand;
use App\Http\Controllers\Controller;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class StartSessionController extends Controller
{
    public function __construct(private readonly StartSession $startSession) {}

    public function __invoke(Request $request, Team $current_team, Presentation $presentation): Response
    {
        Gate::authorize('view', $presentation);

        $validated = $request->validate([
            'word_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ]);

        $this->startSession->execute(new StartSessionCommand(
            presentationId: $presentation->id,
            teamId: $presentation->team_id,
            startedAt: Carbon::now(),
            wordCount: isset($validated['word_count']) ? (int) $validated['word_count'] : null,
        ));

        return response()->noContent();
    }
}
