<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\StopTranslationSession;
use App\Application\Commands\StopTranslationSessionCommand;
use App\Http\Controllers\Controller;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StopTranslationSessionController extends Controller
{
    public function __construct(private readonly StopTranslationSession $stopTranslationSession) {}

    public function __invoke(Request $request, Team $current_team, Presentation $presentation): RedirectResponse
    {
        Gate::authorize('update', $presentation);

        $this->stopTranslationSession->execute(
            new StopTranslationSessionCommand(
                presentationId: $presentation->id,
                userId: $request->user()->id,
            ),
        );

        return redirect()->route('presentations.present', [
            'current_team' => $current_team->slug,
            'presentation' => $presentation->id,
        ]);
    }
}
