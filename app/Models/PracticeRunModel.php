<?php

namespace App\Models;

use App\Domain\Presentation\Entities\PracticeRunEntity;
use Database\Factories\PracticeRunModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property int $duration_seconds
 * @property list<array{slide: int, seconds: int}> $slide_timings
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $flow
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PresentationModel $presentation
 */
#[Fillable(['presentation_id', 'team_id', 'started_at', 'ended_at', 'duration_seconds', 'slide_timings', 'content', 'flow'])]
class PracticeRunModel extends Model
{
    /** @use HasFactory<PracticeRunModelFactory> */
    use HasFactory;

    protected $table = 'practice_runs';

    /**
     * @return BelongsTo<PresentationModel, $this>
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(PresentationModel::class, 'presentation_id');
    }

    public function toEntity(): PracticeRunEntity
    {
        return new PracticeRunEntity(
            presentation_id: $this->presentation_id,
            team_id: $this->team_id,
            started_at: $this->started_at->toDateTimeImmutable(),
            ended_at: $this->ended_at->toDateTimeImmutable(),
            duration_seconds: $this->duration_seconds,
            slide_timings: $this->slide_timings ?? [],
            content: $this->content ?? [],
            flow: $this->flow,
            id: $this->id,
        );
    }

    protected static function newFactory(): PracticeRunModelFactory
    {
        return PracticeRunModelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'slide_timings' => 'array',
            'content' => 'array',
            'flow' => 'array',
        ];
    }
}
