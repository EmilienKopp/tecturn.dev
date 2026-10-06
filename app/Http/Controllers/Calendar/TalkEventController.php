<?php

declare(strict_types=1);

namespace App\Http\Controllers\Calendar;

use App\Application\Actions\Calendar\DeleteTalkEvent;
use App\Application\Actions\Calendar\ScheduleTalkEvent;
use App\Application\Actions\Calendar\UpdateTalkEvent;
use App\Application\Commands\DeleteTalkEventCommand;
use App\Application\Commands\ScheduleTalkEventCommand;
use App\Application\Commands\UpdateTalkEventCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\ScheduleTalkEventRequest;
use App\Http\Requests\Calendar\UpdateTalkEventRequest;
use App\Infrastructure\ReadModels\TalkEventReadModel;
use App\Infrastructure\ReadModels\TalkReadModel;
use App\Models\TalkEvent;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TalkEventController extends Controller
{
    public function __construct(
        private readonly TalkEventReadModel $events,
        private readonly TalkReadModel $talks,
        private readonly ScheduleTalkEvent $scheduleTalkEvent,
        private readonly UpdateTalkEvent $updateTalkEvent,
        private readonly DeleteTalkEvent $deleteTalkEvent,
    ) {}

    public function index(Team $current_team): Response
    {
        return Inertia::render('calendar/Index', [
            'events' => $this->events->listForTeam($current_team->id),
            'talks' => $this->talks->listForTeam($current_team->id),
        ]);
    }

    public function store(ScheduleTalkEventRequest $request, Team $current_team): RedirectResponse
    {
        $this->scheduleTalkEvent->execute(new ScheduleTalkEventCommand(
            teamId: $current_team->id,
            name: (string) $request->validated('name'),
            date: $request->eventDate(),
            startTime: $request->startTime(),
            talkId: $request->talkId(),
        ));

        return redirect()->route('calendar.index', $current_team->slug);
    }

    public function update(UpdateTalkEventRequest $request, Team $current_team, TalkEvent $talk_event): RedirectResponse
    {
        $this->updateTalkEvent->execute(new UpdateTalkEventCommand(
            talkEventId: $talk_event->id,
            name: (string) $request->validated('name'),
            date: $request->eventDate(),
            startTime: $request->startTime(),
            talkId: $request->talkId(),
        ));

        return redirect()->route('calendar.index', $current_team->slug);
    }

    public function destroy(Team $current_team, TalkEvent $talk_event): RedirectResponse
    {
        $this->deleteTalkEvent->execute(new DeleteTalkEventCommand($talk_event->id));

        return redirect()->route('calendar.index', $current_team->slug);
    }
}
