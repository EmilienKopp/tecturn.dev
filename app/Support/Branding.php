<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * The user's branding palette: six named colors reused as shortcuts next to
 * every color picker in the editor. This class is the single source of truth
 * for the palette's keys, defaults, and validation.
 */
class Branding
{
    /**
     * The six branding slots, in display order.
     *
     * @var list<string>
     */
    public const KEYS = ['background', 'primary', 'secondary', 'accent', 'success', 'danger'];

    /**
     * Sensible starting palette applied when a user has not set their own.
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'background' => '#ffffff',
        'primary' => '#2563eb',
        'secondary' => '#64748b',
        'accent' => '#f59e0b',
        'success' => '#16a34a',
        'danger' => '#dc2626',
    ];

    /**
     * Merge a stored (possibly partial or null) palette over the defaults so
     * callers always receive a full set of six valid hex colors.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<string, string>
     */
    public static function merge(?array $stored): array
    {
        $merged = [];

        foreach (self::KEYS as $key) {
            $value = Arr::get($stored ?? [], $key);
            $merged[$key] = is_string($value) && self::isHex($value)
                ? self::normalize($value)
                : self::DEFAULTS[$key];
        }

        return $merged;
    }

    /**
     * Validation rules for a branding update, keyed by `branding.<slot>`.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = ['branding' => ['required', 'array']];

        foreach (self::KEYS as $key) {
            $rules["branding.{$key}"] = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }

    private static function isHex(string $value): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
    }

    private static function normalize(string $value): string
    {
        return strtolower($value);
    }
}
