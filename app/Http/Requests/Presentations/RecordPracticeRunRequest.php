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
     * @return list<array{slide: int, seconds: int}>
     */
    public function slideTimings(): array
    {
        /** @var list<array{slide: int, seconds: int}> $timings */
        $timings = array_values($this->validated('slide_timings', []));

        return $timings;
    }
}
