<?php

declare(strict_types=1);

namespace App\Ai;

use App\Models\UserAiCredential;

/**
 * A decrypted, ready-to-use AI credential for a single deck build. Deliberately
 * lives outside Domain/ValueObjects so it is never type-generated to the
 * frontend: it carries the plaintext API key and must not cross that boundary.
 */
final readonly class ResolvedAiCredential
{
    public function __construct(
        public string $driver,
        public string $model,
        public string $apiKey,
        public ?string $baseUrl = null,
    ) {}

    public static function fromModel(UserAiCredential $credential): self
    {
        return new self(
            driver: $credential->driver,
            model: $credential->model,
            apiKey: $credential->api_key,
            baseUrl: $credential->base_url,
        );
    }
}
