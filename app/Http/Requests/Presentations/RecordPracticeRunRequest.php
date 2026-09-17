<?php

declare(strict_types=1);

namespace App\Http\Requests\Presentations;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class RecordPracticeRunRequest extends FormRequest
{
    /** Upper bound on a single rehearsal — guards against tampering. */
    private const int MAX_DURATION_SECONDS = 60 * 60 * 24;

    public function authorize(): bool
    {
        // Authorization runs in the controller via the presentation gate.
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:'.self::MAX_DURATION_SECONDS],
            'slide_timings' => ['present', 'array'],
            'slide_timings.*.slide' => ['required', 'integer', 'min:0'],
            'slide_timings.*.seconds' => ['required', 'integer', 'min:0', 'max:'.self::MAX_DURATION_SECONDS],
            'step_events' => ['sometimes', 'array'],
            'step_events.*.at_ms' => ['required', 'integer', 'min:0'],
            'step_events.*.slide' => ['required', 'integer', 'min:0'],
            'step_events.*.step' => ['required', 'integer', 'min:0'],
            'audio' => ['nullable', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,video/mp4', 'max:51200'],
        ];
    }

    public function startedAt(): Carbon
    {
        return Carbon::parse((string) $this->validated('started_at'));
    }

    public function endedAt(): Carbon
    {
        return Carbon::parse((string) $this->validated('ended_at'));
    }

    /**
     * Values arrive as strings when the run posts as multipart (a voice
     * recording rides the same request), so cast before they hit the JSON
     * columns — otherwise the frontend renders "0" + 1 as "01".
     *
     * @return list<array{slide: int, seconds: int}>
     */
    public function slideTimings(): array
    {
        return array_map(
            fn (array $timing): array => [
                'slide' => (int) $timing['slide'],
                'seconds' => (int) $timing['seconds'],
            ],
            array_values($this->validated('slide_timings', [])),
        );
    }

    /**
     * @return list<array{at_ms: int, slide: int, step: int}>
     */
    public function stepEvents(): array
    {
        return array_map(
            fn (array $event): array => [
                'at_ms' => (int) $event['at_ms'],
                'slide' => (int) $event['slide'],
                'step' => (int) $event['step'],
            ],
            array_values($this->validated('step_events', []) ?? []),
        );
    }
}
