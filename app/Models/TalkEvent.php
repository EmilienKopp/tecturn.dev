<?php

namespace App\Models;

use App\Domain\Presentation\Entities\TalkEventEntity;
use Database\Factories\TalkEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $talk_id
 * @property string $name
 * @property Carbon $date
 * @property string|null $start_time
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Talk|null $talk
 */
#[Fillable(['team_id', 'talk_id', 'name', 'date', 'start_time'])]
class TalkEvent extends Model
{
    /** @use HasFactory<TalkEventFactory> */
    use HasFactory;

    protected $table = 'talk_events';

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Talk, $this>
     */
    public function talk(): BelongsTo
    {
        return $this->belongsTo(Talk::class);
    }

    public function toEntity(): TalkEventEntity
    {
        return new TalkEventEntity(
            team_id: $this->team_id,
            name: $this->name,
            date: $this->date->toDateTimeImmutable(),
            start_time: $this->start_time !== null
                ? substr($this->start_time, 0, 5)
                : null,
            talk_id: $this->talk_id,
            id: $this->id,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
