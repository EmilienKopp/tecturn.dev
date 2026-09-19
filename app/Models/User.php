<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasTeams;
use App\Support\Branding;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $handle
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $workos_id
 * @property string|null $remember_token
 * @property string $avatar
 * @property string|null $social_x_handle
 * @property string|null $social_github_handle
 * @property array<string, string|null> $branding
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 */
#[Fillable(['name', 'handle', 'email', 'email_verified_at', 'workos_id', 'avatar', 'social_x_handle', 'social_github_handle', 'branding', 'current_team_id'])]
#[Hidden(['workos_id', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The user's branding, always returned as a full set of color and
     * typography slots with defaults filled in for any unset slot.
     *
     * @return Attribute<array<string, string|null>, array<string, string|null>>
     */
    protected function branding(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => Branding::merge($value ? json_decode($value, true) : null),
            set: fn (?array $value): string => (string) json_encode(Branding::merge($value)),
        );
    }

    /**
     * Whether this user is an authorized admin (email in the allowlist).
     */
    public function isAdmin(): bool
    {
        return in_array(Str::lower($this->email), config('admin.emails', []), true);
    }

    public static function normalizeHandle(mixed $handle): ?string
    {
        if (! is_string($handle)) {
            return null;
        }

        $normalized = Str::lower(ltrim(trim($handle), '@'));
        $normalized = (string) preg_replace('/[^a-z0-9._-]+/', '-', $normalized);
        $normalized = trim($normalized, '.-_');

        return $normalized === '' ? null : $normalized;
    }

    public static function generateUniqueHandle(string $value, ?int $ignoreId = null): string
    {
        $base = self::normalizeHandle($value) ?? 'user';
        $base = mb_substr($base, 0, 32);
        $base = trim($base, '.-_');

        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $suffix = 2;

        while (self::query()
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('handle', $candidate)
            ->exists()) {
            $suffixString = '-'.$suffix;
            $trimmed = mb_substr($base, 0, max(1, 32 - mb_strlen($suffixString)));
            $trimmed = trim($trimmed, '.-_');
            $candidate = ($trimmed === '' ? 'user' : $trimmed).$suffixString;
            $suffix++;
        }

        return $candidate;
    }
}
