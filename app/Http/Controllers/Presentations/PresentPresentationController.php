<?php

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\PresentationReadModel;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PresentPresentationController extends Controller
{
    public function __construct(private readonly PresentationReadModel $presentations) {}

    public function __invoke(Request $request, Team $current_team, Presentation $presentation): Response
    {
        Gate::authorize('view', $presentation);

        return Inertia::render('presentations/Present', [
            'presentation' => $this->presentations->findForPresent($presentation->id),
            'sourcePdfUrl' => $presentation->sourcePdfUrl(),
            'viewerUrl' => route('presentations.viewer', ['presentation' => $presentation->embed_token]),
            'testMode' => $request->boolean('test'),
            'rehearsalMode' => $request->boolean('rehearsal'),
            'rehearsalRoutes' => [
                'store' => route('presentations.rehearsal.store', [
                    'current_team' => $current_team->slug,
                    'presentation' => $presentation->id,
                ]),
            ],
            'sessionRoutes' => [
                'start' => route('presentations.session.start', [
                    'current_team' => $current_team->slug,
                    'presentation' => $presentation->id,
                ]),
                'close' => route('presentations.session.end', [
                    'current_team' => $current_team->slug,
                    'presentation' => $presentation->id,
                ]),
            ],
            'translationRoutes' => [
                'start' => route('presentations.translation-session.start', [
                    'current_team' => $current_team->slug,
                    'presentation' => $presentation->id,
                ]),
                'stop' => route('presentations.translation-session.stop', [
                    'current_team' => $current_team->slug,
                    'presentation' => $presentation->id,
                ]),
            ],
        ]);
    }
}
