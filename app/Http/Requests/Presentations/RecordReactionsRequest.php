<?php

declare(strict_types=1);

namespace App\Http\Requests\Presentations;

use App\Domain\Presentation\ValueObjects\TalkSettings;
use App\Models\Presentation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordReactionsRequest extends FormRequest
{
    /** Upper bound per emoji in a single flush — guards against tampering. */
    private const int MAX_PER_EMOJI = 500;

    public function authorize(): bool
    {
        // Anonymous audience endpoint — access is gated by the embed token in
        // the route, matching SendReactionController.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string|Closure>
     */
    public function rules(): array
    {
        $allowed = $this->allowedEmojis();

        return [
            'viewerId' => ['required', 'string', 'max:64'],
            'leaving' => ['sometimes', 'boolean'],
            'counts' => ['sometimes', 'array', function (string $attribute, mixed $value, Closure $fail) use ($allowed): void {
                if (! is_array($value)) {
                    return;
                }

                foreach (array_keys($value) as $emoji) {
                    if (! in_array($emoji, $allowed, true)) {
                        $fail('An unsupported reaction was sent.');

                        return;
                    }
                }
            }],
            'counts.*' => ['integer', 'min:1', 'max:'.self::MAX_PER_EMOJI],
        ];
    }

    /**
     * The reactions this presentation accepts — its customised set, or the
     * defaults when it has none.
     *
     * @return list<string>
     */
    private function allowedEmojis(): array
    {
        $presentation = $this->route('presentation');

        return $presentation instanceof Presentation
            ? TalkSettings::fromArray($presentation->talk_settings ?? [])->reactions
            : TalkSettings::DEFAULT_REACTIONS;
    }

    /**
     * The reaction tallies keyed by emoji, pruned to the allowed set.
     *
     * @return array<string, int>
     */
    public function reactionCounts(): array
    {
        /** @var array<string, int> $counts */
        $counts = $this->validated('counts', []);

        return $counts;
    }
}
