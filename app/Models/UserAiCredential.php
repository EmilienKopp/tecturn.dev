<?php

namespace App\Models;

use Database\Factories\UserAiCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Ai\Enums\Lab;

/**
 * A user's own AI provider credential for deck generation (bring your own AI).
 * The `api_key` is encrypted at rest and never serialized to the frontend
 * (hidden); the UI shows only {@see self::maskedKey()}.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $driver
 * @property string $model
 * @property string|null $base_url
 * @property string $api_key
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['label', 'driver', 'model', 'base_url', 'api_key', 'is_default'])]
#[Hidden(['api_key'])]
class UserAiCredential extends Model
{
    /** @use HasFactory<UserAiCredentialFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The provider as a Lab enum, or null if the stored driver is not a known
     * provider (e.g. it was valid at save time but has since been removed).
     */
    public function lab(): ?Lab
    {
        return Lab::tryFrom($this->driver);
    }

    /**
     * A display-safe hint of the key: last four characters only. Never returns
     * the full key.
     */
    public function maskedKey(): string
    {
        $key = $this->api_key;

        return '••••'.mb_substr($key, -4);
    }
}
