<?php

namespace App\Models;

use App\Domain\Presentation\Entities\RehearsalEntity;
use Database\Factories\RehearsalModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property int $duration_seconds
 * @property list<array{slide: int, seconds: int}> $slide_timings
 * @property list<array{at_ms: int, slide: int, step: int}>|null $step_events
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $flow
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PresentationModel $presentation
 */
#[Fillable(['presentation_id', 'team_id', 'started_at', 'ended_at', 'duration_seconds', 'slide_timings', 'step_events', 'content', 'flow'])]
class RehearsalModel extends Model implements HasMedia
{
    /** @use HasFactory<RehearsalModelFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const string RECORDING_COLLECTION = 'recording';

    protected $table = 'practice_runs';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::RECORDING_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['audio/webm', 'video/webm', 'audio/ogg', 'audio/mp4', 'video/mp4']);
    }

    /**
     * @return BelongsTo<PresentationModel, $this>
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(PresentationModel::class, 'presentation_id');
    }

    /**
     * @return HasMany<RehearsalReviewModel, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(RehearsalReviewModel::class, 'practice_run_id');
    }

    public function toEntity(): RehearsalEntity
    {
        return new RehearsalEntity(
            presentation_id: $this->presentation_id,
            team_id: $this->team_id,
            started_at: $this->started_at->toDateTimeImmutable(),
            ended_at: $this->ended_at->toDateTimeImmutable(),
            duration_seconds: $this->duration_seconds,
            slide_timings: $this->slide_timings ?? [],
            content: $this->content ?? [],
            flow: $this->flow,
            step_events: $this->step_events,
            id: $this->id,
        );
    }

    protected static function newFactory(): RehearsalModelFactory
    {
        return RehearsalModelFactory::new();
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
            'step_events' => 'array',
            'content' => 'array',
            'flow' => 'array',
        ];
    }
}
