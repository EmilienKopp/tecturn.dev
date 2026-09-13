<?php

declare(strict_types=1);

namespace App\Support\FeatureFlags;

use App\Models\Team;
use App\Support\Features;
use Laravel\Pennant\Feature;

/**
 * Assembles feature-flag definitions together with their resolved values for
 * the admin panel. Reads only; writes go through the SetFeatureFlag action.
 */
class FeatureFlagsPresenter
{
    /**
     * Global flags with their current values.
     *
     * @return list<array{key: string, label: string, description: string, type: string, scope: string, options: list<array{value: string, label: string}>, value: bool|string}>
     */
    public function globalFlags(): array
    {
        return array_map(function (FeatureDefinition $flag): array {
            return [
                ...$flag->toArray(),
                'value' => Feature::for(Features::GLOBAL_SCOPE)->value($flag->key),
            ];
        }, FeatureCatalog::global());
    }

    /**
     * Team-scoped flags resolved for a specific team.
     *
     * @return list<array{key: string, label: string, description: string, type: string, scope: string, options: list<array{value: string, label: string}>, value: bool|string}>
     */
    public function teamFlags(Team $team): array
    {
        return array_map(function (FeatureDefinition $flag) use ($team): array {
            return [
                ...$flag->toArray(),
                'value' => Feature::for($team)->value($flag->key),
            ];
        }, FeatureCatalog::team());
    }
}
