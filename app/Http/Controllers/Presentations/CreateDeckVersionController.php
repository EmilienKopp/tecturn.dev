<?php

declare(strict_types=1);

namespace App\Http\Controllers\Presentations;

use App\Application\Actions\Presentations\CreateDeckVersion;
use App\Application\Commands\CreateDeckVersionCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presentations\CreateDeckVersionRequest;
use App\Models\Presentation;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CreateDeckVersionController extends Controller
{
    public function __construct(
        private readonly CreateDeckVersion $createDeckVersion,
    ) {}

    public function __invoke(CreateDeckVersionRequest $request, Team $current_team, Presentation $presentation): RedirectResponse
    {
        Gate::authorize('update', $presentation);

        $copy = $this->createDeckVersion->execute(new CreateDeckVersionCommand(
            presentationId: $presentation->id,
            bump: $request->bump(),
        ));

        return redirect()->route('presentations.edit', [
            'current_team' => $current_team->slug,
            'presentation' => $copy->id,
        ]);
    }
}
