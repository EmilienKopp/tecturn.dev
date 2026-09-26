<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use App\Domain\Presentation\Exceptions\InvalidPresentationContent;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

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
        /**
         * Presenter-declared slide count for external decks. Google Slides can't
         * be introspected across origins, so the presenter sets this manually; it
         * drives the dock's slide counter and paces the deck. Null = unset (or an
         * editor deck, which counts its own slides).
         */
        public ?int $slideCount = null,
    ) {
        if ($this->type === SourceType::GoogleSlides) {
            if ($this->externalUrl === null || ! $this->isValidGoogleSlidesUrl($this->externalUrl)) {
                throw new InvalidPresentationContent('A valid Google Slides URL is required.');
            }
        } elseif ($this->externalUrl !== null) {
            throw new InvalidPresentationContent('Only Google Slides decks may carry an external URL.');
        }

        if ($this->slideCount !== null && $this->slideCount < 1) {
            throw new InvalidPresentationContent('Slide count must be at least 1.');
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
            slideCount: isset($data['slideCount']) && is_numeric($data['slideCount'])
                ? (int) $data['slideCount']
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
            'slideCount' => $this->slideCount,
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
