<?php

namespace App\Models;

use App\Domain\Presentation\Entities\TalkEntity;
use Database\Factories\TalkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Collection<int, Presentation> $presentations
 */
#[Fillable(['team_id', 'title'])]
class Talk extends Model
{
    /** @use HasFactory<TalkFactory> */
    use HasFactory;

    protected $table = 'talks';

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Presentation, $this>
     */
    public function presentations(): HasMany
    {
        return $this->hasMany(Presentation::class);
    }

    public function toEntity(): TalkEntity
    {
        return new TalkEntity(
            team_id: $this->team_id,
            title: $this->title,
            id: $this->id,
        );
    }
}
