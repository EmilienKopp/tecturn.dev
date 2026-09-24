<?php

namespace App\Models;

use App\Domain\Beta\Entities\BetaRequestEntity;
use App\Enums\BetaRequestStatus;
use Database\Factories\BetaRequestFactory;
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
class BetaRequest extends Model
{
    /** @use HasFactory<BetaRequestFactory> */
    use HasFactory;

    protected $table = 'beta_requests';

    public function toEntity(): BetaRequestEntity
    {
        return BetaRequestEntity::create(
            name: $this->name,
            email: $this->email,
            message: $this->message,
        );
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
