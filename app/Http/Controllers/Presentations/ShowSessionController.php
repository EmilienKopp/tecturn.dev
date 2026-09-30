<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Http\Controllers\Controller;
use App\Infrastructure\ReadModels\SessionReadModel;
use App\Models\PresentationSession;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class ShowSessionController extends Controller
{
    public function __construct(private readonly SessionReadModel $sessions) {}

    public function __invoke(Team $current_team, PresentationSession $session): Response
    {
        abort_unless($session->team_id === $current_team->id, 404);

        $detail = $this->sessions->detail($session->id);

        abort_unless($detail !== null, 404);

        return Inertia::render('sessions/Show', $detail);
    }
}
