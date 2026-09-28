<?php

declare(strict_types=1);

namespace App\Domain\Presentation\ValueObjects;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class TalkSettings
{
    /**
     * The reaction set shipped by default: the seed for every new presentation
     * and the fallback whenever a custom set is empty or invalid.
     *
     * @var list<string>
     */
    public const array DEFAULT_REACTIONS = ['👏', '❤️', '😂', '🤯', '🙌', '🔥'];

    /** Upper bound on how many reactions an author can customise. */
    public const int MAX_REACTIONS = 10;

    /**
     * @param  list<string>  $reactions
     */
    public function __construct(
        public bool $showReactions = false,
        public bool $showDock = true,
        public bool $showTranslation = true,
        public string $timerMode = 'elapsed',
        public ?int $durationMinutes = null,
        public bool $autoSave = false,
        public FooterSettings $footer = new FooterSettings,
        public array $reactions = self::DEFAULT_REACTIONS,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            showReactions: (bool) ($data['showReactions'] ?? false),
            showDock: (bool) ($data['showDock'] ?? true),
            showTranslation: (bool) ($data['showTranslation'] ?? true),
            timerMode: in_array($data['timerMode'] ?? 'elapsed', ['elapsed', 'countdown'], true)
                ? (string) ($data['timerMode'] ?? 'elapsed')
                : 'elapsed',
            durationMinutes: isset($data['durationMinutes']) && is_numeric($data['durationMinutes'])
                ? (int) $data['durationMinutes']
                : null,
            autoSave: (bool) ($data['autoSave'] ?? false),
            footer: FooterSettings::fromArray(is_array($data['footer'] ?? null) ? $data['footer'] : []),
            reactions: self::sanitizeReactions($data['reactions'] ?? null),
        );
    }

    /**
     * Keeps non-empty, unique reactions, caps at MAX_REACTIONS, and falls back
     * to the defaults when nothing usable is left.
     *
     * @return list<string>
     */
    private static function sanitizeReactions(mixed $reactions): array
    {
        if (! is_array($reactions)) {
            return self::DEFAULT_REACTIONS;
        }

        $clean = [];

        foreach ($reactions as $reaction) {
            if (! is_string($reaction)) {
                continue;
            }

            $trimmed = trim($reaction);

            if ($trimmed === '' || in_array($trimmed, $clean, true)) {
                continue;
            }

            $clean[] = $trimmed;

            if (count($clean) >= self::MAX_REACTIONS) {
                break;
            }
        }

        return $clean === [] ? self::DEFAULT_REACTIONS : $clean;
    }

    public static function defaults(): self
    {
        return new self;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'showReactions' => $this->showReactions,
            'showDock' => $this->showDock,
            'showTranslation' => $this->showTranslation,
            'timerMode' => $this->timerMode,
            'durationMinutes' => $this->durationMinutes,
            'autoSave' => $this->autoSave,
            'footer' => $this->footer->toArray(),
            'reactions' => $this->reactions,
        ];
    }
}
