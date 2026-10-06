<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Models\Team;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleTalkEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Team $team */
        $team = $this->route('current_team');

        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'talk_id' => [
                'nullable',
                'integer',
                Rule::exists('talks', 'id')->where('team_id', $team->id),
            ],
        ];
    }

    public function eventDate(): DateTimeInterface
    {
        return new DateTimeImmutable((string) $this->validated('date'));
    }

    public function startTime(): ?string
    {
        $value = $this->validated('start_time');

        return $value !== null ? (string) $value : null;
    }

    public function talkId(): ?int
    {
        $value = $this->validated('talk_id');

        return $value !== null ? (int) $value : null;
    }
}
