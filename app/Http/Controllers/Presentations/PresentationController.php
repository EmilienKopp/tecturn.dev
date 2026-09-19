<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\CreatePresentation;
use App\Application\Actions\Presentations\DeletePresentation;
use App\Application\Actions\Presentations\UpdatePresentation;
use App\Application\Commands\CreatePresentationCommand;
use App\Application\Commands\DeletePresentationCommand;
use App\Application\Commands\UpdatePresentationCommand;
use App\Domain\Presentation\Exceptions\InvalidFlowGraph;
use App\Domain\Presentation\Exceptions\InvalidPresentationContent;
use App\Domain\Presentation\ValueObjects\FlowGraph;
use App\Domain\Presentation\ValueObjects\PresentationContent;
use App\Domain\Presentation\ValueObjects\TalkSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\CreatePresentationRequest;
use App\Http\Requests\Presentations\UpdatePresentationRequest;
use App\Infrastructure\ReadModels\PresentationReadModel;
use App\Models\PresentationModel;
use App\Models\Team;
use App\Presentation\EmbedCache;
use App\Presentation\GeneratingDeckTally;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PresentationController extends Controller
{
    public function __construct(
        private readonly PresentationReadModel $presentations,
        private readonly EmbedCache $embeds,
        private readonly CreatePresentation $createPresentation,
        private readonly UpdatePresentation $updatePresentation,
        private readonly DeletePresentation $deletePresentation,
    ) {}

    public function index(Team $current_team, GeneratingDeckTally $tally): Response
    {
        return Inertia::render('presentations/Index', [
            'presentations' => $this->presentations->listForTeam($current_team->id),
            'generatingCount' => $tally->count($current_team->id),
        ]);
    }

    public function store(CreatePresentationRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [PresentationModel::class, $current_team]);

        $presentation = $this->createPresentation->execute(
            new CreatePresentationCommand(
                team_id: $current_team->id,
                name: $request->validated('name'),
                slide_background: $request->user()->branding['background'],
            ),
        );

        return redirect()->route('presentations.edit', [
            'current_team' => $current_team->slug,
            'presentation' => $presentation->id,
        ]);
    }

    public function edit(Team $current_team, PresentationModel $presentation): Response
    {
        Gate::authorize('view', $presentation);

        return Inertia::render('presentations/Editor', [
            'presentation' => $this->presentations->findForEditor($presentation->id),
            'embed' => [
                'url' => route('presentations.embed', ['presentation' => $presentation->embed_token]),
                'tag' => $this->embeds->customElementTag($presentation->embed_token),
            ],
            'viewerUrl' => route('presentations.viewer', ['presentation' => $presentation->embed_token]),
        ]);
    }

    public function update(
        UpdatePresentationRequest $request,
        Team $current_team,
        PresentationModel $presentation,
    ): RedirectResponse {
        Gate::authorize('update', $presentation);

        try {
            $content = $request->has('content')
                ? PresentationContent::fromArray($request->validated('content'))
                : null;
        } catch (InvalidPresentationContent $exception) {
            throw ValidationException::withMessages(['content' => $exception->getMessage()]);
        }

        try {
            $flow = $request->has('flow')
                ? FlowGraph::fromArray($request->validated('flow'))
                : null;
        } catch (InvalidFlowGraph $exception) {
            throw ValidationException::withMessages(['flow' => $exception->getMessage()]);
        }

        $talkSettings = $request->has('talk_settings')
            ? TalkSettings::fromArray($request->validated('talk_settings'))
            : null;

        try {
            $this->updatePresentation->execute(
                new UpdatePresentationCommand(
                    presentation_id: $presentation->id,
                    name: $request->validated('name', null),
                    isPrivate: $request->validated('is_private', null),
                    content: $content,
                    talkSettings: $talkSettings,
                    flow: $flow,
                ),
            );
        } catch (InvalidFlowGraph $exception) {
            // replaceFlow() cross-checks slide references against the content.
            throw ValidationException::withMessages(['flow' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Presentation saved.')]);

        return back();
    }

    public function destroy(Team $current_team, PresentationModel $presentation): RedirectResponse
    {
        Gate::authorize('delete', $presentation);

        $this->deletePresentation->execute(
            new DeletePresentationCommand(presentation_id: $presentation->id),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Presentation deleted.')]);

        return redirect()->route('presentations.index', ['current_team' => $current_team->slug]);
    }
}
