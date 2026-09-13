<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Application\Actions\Features\SetFeatureFlag;
use App\Application\Commands\SetFeatureFlagCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeatureFlagRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UpdateFeatureFlagController extends Controller
{
    public function __construct(private readonly SetFeatureFlag $setFeatureFlag) {}

    public function __invoke(UpdateFeatureFlagRequest $request): RedirectResponse
    {
        $this->setFeatureFlag->execute(new SetFeatureFlagCommand(
            key: $request->validated('key'),
            value: $request->resolvedValue(),
            teamId: $request->teamId(),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Feature updated.')]);

        return back();
    }
}
