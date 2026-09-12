<?php

namespace App\Models;

use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Enums\BetaRequestStatus;
use Database\Factories\BetaRequestModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $email_hash
 * @property string|null $message
 * @property BetaRequestStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'email_hash', 'message', 'status'])]
class BetaRequestModel extends Model
{
    /** @use HasFactory<BetaRequestModelFactory> */
    use HasFactory;

    protected $table = 'beta_requests';

    public function toEntity(): BetaRequestEntity
    {
        return new BetaRequestEntity(
            name: $this->name,
            email: $this->email,
            message: $this->message,
            status: $this->status,
            id: $this->id,
            created_at: $this->created_at?->toDateTimeImmutable(),
            updated_at: $this->updated_at?->toDateTimeImmutable(),
        );
    }

    protected static function newFactory(): BetaRequestModelFactory
    {
        return BetaRequestModelFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'status' => BetaRequestStatus::class,
        ];
    }
}
