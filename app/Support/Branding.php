<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * The user's branding: six named colors plus default typography. Branding is
 * the single source of defaults for the editor — the background slot (hex or
 * linear-gradient) is the default slide background, the primary slot is the
 * default text color, and the typography slots seed new text blocks. This
 * class is the single source of truth for the keys, defaults, and validation.
 */
class Branding
{
    /**
     * The six color slots, in display order.
     *
     * @var list<string>
     */
    public const KEYS = ['background', 'primary', 'secondary', 'accent', 'success', 'danger'];

    /**
     * Default typography slots; null means "no explicit default" and the
     * editor's built-in styling applies.
     *
     * @var list<string>
     */
    public const TYPOGRAPHY_KEYS = ['fontFamily', 'fontSize', 'fontWeight'];

    public const FONT_WEIGHTS = ['normal', 'medium', 'semibold', 'bold'];

    /**
     * Sensible starting palette applied when a user has not set their own.
     *
     * @var array<string, string|null>
     */
    public const DEFAULTS = [
        'background' => '#ffffff',
        'primary' => '#2563eb',
        'secondary' => '#64748b',
        'accent' => '#f59e0b',
        'success' => '#16a34a',
        'danger' => '#dc2626',
        'fontFamily' => null,
        'fontSize' => null,
        'fontWeight' => null,
    ];

    /**
     * Matches the exact `linear-gradient(...)` shape the editor's gradient
     * builder emits (buildLinearGradient in resources/js/lib/tecturn/background.ts):
     * an integer angle and two to four hex stops. Only the background slot may
     * hold one; it doubles as the default background of new slides.
     */
    private const GRADIENT_PATTERN = '/^linear-gradient\(\d{1,3}deg(?:,\s*#[0-9a-fA-F]{6}){2,4}\)$/';

    private const FONT_SIZE_PATTERN = '/^\d+(\.\d+)?(rem|px|em)$/';

    /**
     * Merge a stored (possibly partial or null) branding over the defaults so
     * callers always receive a full set of valid slots.
     *
     * @param  array<string, mixed>|null  $stored
     * @return array<string, string|null>
     */
    public static function merge(?array $stored): array
    {
        $merged = [];

        foreach (self::KEYS as $key) {
            $value = Arr::get($stored ?? [], $key);
            $merged[$key] = is_string($value) && self::isValidColorFor($key, $value)
                ? strtolower($value)
                : self::DEFAULTS[$key];
        }

        foreach (self::TYPOGRAPHY_KEYS as $key) {
            $value = Arr::get($stored ?? [], $key);
            $merged[$key] = is_string($value) && $value !== '' ? $value : null;
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
            $rules["branding.{$key}"] = $key === 'background'
                ? ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$|'.trim(self::GRADIENT_PATTERN, '/').'/']
                : ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        $rules['branding.fontFamily'] = ['nullable', 'string', 'max:100'];
        $rules['branding.fontSize'] = ['nullable', 'string', 'regex:'.self::FONT_SIZE_PATTERN];
        $rules['branding.fontWeight'] = ['nullable', 'string', 'in:'.implode(',', self::FONT_WEIGHTS)];

        return $rules;
    }

    private static function isHex(string $value): bool
    {
        return (bool) preg_match('/^#[0-9a-fA-F]{6}$/', $value);
    }

    private static function isValidColorFor(string $key, string $value): bool
    {
        if (self::isHex($value)) {
            return true;
        }

        return $key === 'background' && (bool) preg_match(self::GRADIENT_PATTERN, $value);
    }
}
