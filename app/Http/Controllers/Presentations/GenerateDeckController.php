<?php

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\GenerateDeckFromPlan;
use App\Application\Commands\GenerateDeckFromPlanCommand;
use App\Domain\Presentation\Entities\PresentationEntity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\GenerateDeckRequest;
use App\Models\PresentationModel;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class GenerateDeckController extends Controller
{
    public function __construct(private readonly GenerateDeckFromPlan $generateDeck) {}

    public function __invoke(GenerateDeckRequest $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', [PresentationModel::class, $current_team]);

        try {
            $presentation = $this->generateDeck->execute(
                new GenerateDeckFromPlanCommand(
                    team_id: $current_team->id,
                    name: (string) $request->validated('name', ''),
                    plan: $request->validated('plan'),
                ),
            );
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'plan' => 'The draft could not be generated. Please try again or refine your outline.',
            ]);
        }

        /** @var PresentationEntity $presentation */
        return redirect()->route('presentations.edit', [
            'current_team' => $current_team->slug,
            'presentation' => $presentation->id,
        ]);
    }
}
