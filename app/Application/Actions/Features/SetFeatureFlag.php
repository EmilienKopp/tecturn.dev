<?php

declare(strict_types=1);

namespace App\Application\Actions\Features;

use App\Application\Commands\SetFeatureFlagCommand;
use App\Models\Team;
use App\Support\FeatureFlags\FeatureCatalog;
use App\Support\FeatureFlags\FeatureDefinition;
use App\Support\Features;
use InvalidArgumentException;
use Laravel\Pennant\Feature;

class SetFeatureFlag
{
    /**
     * Persist a new value for a managed feature flag, either globally or for a
     * single team, after checking it against the catalog.
     */
    public function execute(SetFeatureFlagCommand $command): void
    {
        $flag = FeatureCatalog::find($command->key);

        if ($flag === null || ! $flag->accepts($command->value)) {
            throw new InvalidArgumentException("Invalid value for feature flag [{$command->key}].");
        }

        $scope = $this->resolveScope($flag, $command->teamId);

        if ($flag->isBoolean()) {
            $command->value
                ? Feature::for($scope)->activate($flag->key)
                : Feature::for($scope)->deactivate($flag->key);

            return;
        }

        Feature::for($scope)->activate($flag->key, $command->value);
    }

    private function resolveScope(FeatureDefinition $flag, ?int $teamId): Team|string
    {
        if ($flag->isGlobal()) {
            return Features::GLOBAL_SCOPE;
        }

        if ($teamId === null) {
            throw new InvalidArgumentException("Feature flag [{$flag->key}] requires a team.");
        }

        return Team::findOrFail($teamId);
    }
}
