<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use App\Domain\Presentation\Exceptions\InvalidPresentationContent;
use Splitstack\Typewriter\Attributes\TypeScript;

/**
 * The origin of a presentation's slides. Editor decks store no external URL and
 * render from content + flow; PDF decks keep their file in the `source` media
 * collection; Google Slides decks embed a published URL.
 */
#[TypeScript]
readonly class PresentationSource
{
    public function __construct(
        public SourceType $type = SourceType::Editor,
        /** Published Google Slides URL — only set for Google Slides decks. */
        public ?string $externalUrl = null,
    ) {
        if ($this->type === SourceType::GoogleSlides) {
            if ($this->externalUrl === null || ! $this->isValidGoogleSlidesUrl($this->externalUrl)) {
                throw new InvalidPresentationContent('A valid Google Slides URL is required.');
            }
        } elseif ($this->externalUrl !== null) {
            throw new InvalidPresentationContent('Only Google Slides decks may carry an external URL.');
        }
    }

    public static function editor(): self
    {
        return new self;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = SourceType::tryFrom((string) ($data['type'] ?? SourceType::Editor->value)) ?? SourceType::Editor;

        return new self(
            type: $type,
            externalUrl: $type === SourceType::GoogleSlides
                ? (isset($data['externalUrl']) ? (string) $data['externalUrl'] : null)
                : null,
        );
    }

    public function isExternal(): bool
    {
        return $this->type->isExternal();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'externalUrl' => $this->externalUrl,
        ];
    }

    private function isValidGoogleSlidesUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? null) !== 'https') {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        return $host === 'docs.google.com' && str_contains($path, '/presentation/');
    }
}
