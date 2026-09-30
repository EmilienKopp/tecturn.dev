<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\DeleteSession;
use App\Application\Commands\DeleteSessionCommand;
use App\Http\Controllers\Controller;
use App\Models\PresentationSession;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class DeleteSessionController extends Controller
{
    public function __construct(private readonly DeleteSession $deleteSession) {}

    public function __invoke(Team $current_team, PresentationSession $session): RedirectResponse
    {
        abort_unless($session->team_id === $current_team->id, 404);

        $this->deleteSession->execute(new DeleteSessionCommand(
            sessionId: $session->id,
        ));

        return back();
    }
}
