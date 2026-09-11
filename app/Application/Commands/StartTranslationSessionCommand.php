<?php

declare(strict_types=1);

namespace App\Application\Commands;

readonly class StartTranslationSessionCommand
{
    /**
     * @param  list<string>  $languages  Language codes the presenter wants captions for.
     */
    public function __construct(
        public int $presentationId,
        public int $userId,
        public ?string $sourceLanguage = null,
        public ?string $eventId = null,
        public array $languages = [],
    ) {}
}
