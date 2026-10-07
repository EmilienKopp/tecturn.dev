import { page } from '@inertiajs/svelte';
import { isGradientBackground } from '@/lib/tecturn/background';
import type { BrandingColors } from '@/types/auth';

/** The six branding slots, in display order. Mirrors App\Support\Branding::KEYS. */
export const BRANDING_KEYS = [
    'background',
    'primary',
    'secondary',
    'accent',
    'success',
    'danger',
] as const;

export type BrandingKey = (typeof BRANDING_KEYS)[number];

/** The daisyUI theme variable that feeds each branding slot. */
const THEME_COLOR_VARS: Record<BrandingKey, string> = {
    background: '--color-base-100',
    primary: '--color-primary',
    secondary: '--color-secondary',
    accent: '--color-accent',
    success: '--color-success',
    danger: '--color-error',
};

export const BRANDING_LABELS: Record<BrandingKey, string> = {
    background: 'Background',
    primary: 'Primary',
    secondary: 'Secondary',
    accent: 'Accent',
    success: 'Success',
    danger: 'Danger',
};

/** The typography slots, in display order. Mirrors App\Support\Branding::TYPOGRAPHY_KEYS. */
export const BRANDING_TYPOGRAPHY_KEYS = [
    'fontFamily',
    'fontSize',
    'fontWeight',
] as const;

export type BrandingTypographyKey = (typeof BRANDING_TYPOGRAPHY_KEYS)[number];

export const BRANDING_FONT_SIZES = [
    '1rem',
    '1.5rem',
    '2rem',
    '2.5rem',
    '3rem',
    '4rem',
] as const;

export const BRANDING_FONT_WEIGHTS = [
    'normal',
    'medium',
    'semibold',
    'bold',
] as const;

/** Fallback palette used before the user's branding has loaded. Mirrors the backend defaults. */
export const BRANDING_FALLBACK: BrandingColors = {
    background: '#ffffff',
    primary: '#2563eb',
    secondary: '#64748b',
    accent: '#f59e0b',
    success: '#16a34a',
    danger: '#dc2626',
    fontFamily: null,
    fontSize: null,
    fontWeight: null,
};

/**
 * Branding colors read from a daisyUI theme's CSS variables. With no
 * argument, reads the theme currently active on the document root; given a
 * theme name, resolves that theme through a hidden probe element. Reads the
 * daisy tokens directly (not the legacy shadcn aliases) because those are
 * the only variables scoped to `[data-theme]` and thus probe-safe.
 * Typography slots stay at their fallback defaults.
 */
export function brandingColorsFromTheme(theme?: string): BrandingColors {
    const colors = { ...BRANDING_FALLBACK };

    if (typeof document === 'undefined') {
        return colors;
    }

    let element: HTMLElement = document.documentElement;
    let probe: HTMLElement | null = null;

    if (theme && theme !== document.documentElement.dataset.theme) {
        probe = document.createElement('div');
        probe.dataset.theme = theme;
        probe.style.position = 'fixed';
        probe.style.visibility = 'hidden';
        document.body.appendChild(probe);
        element = probe;
    }

    const styles = getComputedStyle(element);

    for (const key of BRANDING_KEYS) {
        const value = styles.getPropertyValue(THEME_COLOR_VARS[key]).trim();

        if (value) {
            colors[key] = value;
        }
    }

    probe?.remove();

    return colors;
}

export interface BrandingSwatch {
    key: BrandingKey;
    label: string;
    value: string;
}

/**
 * The signed-in user's branding palette from Inertia shared props. Reads the
 * `page` proxy, so call it inside a reactive context (e.g. a `$derived`) to
 * stay in sync after the user updates their branding.
 */
export function currentBranding(): BrandingColors {
    const user = (
        page.props as { auth?: { user?: { branding?: BrandingColors } } }
    )?.auth?.user;

    return user?.branding ?? BRANDING_FALLBACK;
}

/**
 * The branding palette as an ordered list of swatches for color-picker
 * shortcuts. A gradient background is skipped — ColorField can only apply
 * solid colors.
 */
export function brandingSwatches(): BrandingSwatch[] {
    const branding = currentBranding();

    return BRANDING_KEYS.map((key) => ({
        key,
        label: BRANDING_LABELS[key],
        value: branding[key],
    })).filter((swatch) => !isGradientBackground(swatch.value));
}
