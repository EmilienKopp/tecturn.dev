<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\UserAiCredential;

/**
 * Bridges a stored {@see UserAiCredential} to the Laravel AI SDK at runtime for
 * a single deck build. Loading and applying happen inside the queued job (its
 * own process), so mutating the `ai.providers` config here is safe and never
 * bleeds into other requests. The plaintext key stays in this process only; it
 * is never placed on the queue payload.
 */
class AiCredentialResolver
{
    /**
     * Load and decrypt a credential by id, or null if it no longer exists.
     */
    public function resolve(int $id): ?ResolvedAiCredential
    {
        $credential = UserAiCredential::find($id);

        return $credential === null ? null : ResolvedAiCredential::fromModel($credential);
    }

    /**
     * Point the Laravel AI SDK at the user's credential and return the
     * provider/model pair to pass to the agent's `prompt()` call. The provider
     * name equals the driver name; that entry's config is overwritten for this
     * process only.
     *
     * @return array{provider: string, model: string}
     */
    public function apply(ResolvedAiCredential $credential): array
    {
        $provider = $credential->driver;

        $providerConfig = array_filter([
            'driver' => $credential->driver,
            'key' => $credential->apiKey,
            'url' => $credential->baseUrl,
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        // Moonshot AI requires temperature to be exactly 1. Override the agent's
        // temperature attribute when using Moonshot models by setting it in the
        // provider config.
        if ($this->isMoonshotModel($credential)) {
            $providerConfig['temperature'] = 1.0;
        }

        config(['ai.providers.'.$provider => $providerConfig]);

        return ['provider' => $provider, 'model' => $credential->model];
    }

    /**
     * Check if the credential is using a Moonshot AI model.
     */
    private function isMoonshotModel(ResolvedAiCredential $credential): bool
    {
        return $credential->baseUrl === 'https://api.moonshot.ai/v1'
            || str_starts_with($credential->model, 'kimi-');
    }
}
