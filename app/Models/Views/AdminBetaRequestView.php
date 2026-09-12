<?php

namespace App\Models\Views;

use App\Enums\BetaRequestStatus;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property string $name
 * @property string $email Encrypted ciphertext; decrypt via the ReadModel.
 * @property string|null $message
 * @property BetaRequestStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AdminBetaRequestView extends ReadOnlyModel
{
    protected $table = 'admin_beta_requests';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BetaRequestStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
