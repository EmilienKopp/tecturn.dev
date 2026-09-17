import { page } from '@inertiajs/svelte';
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

export const BRANDING_LABELS: Record<BrandingKey, string> = {
    background: 'Background',
    primary: 'Primary',
    secondary: 'Secondary',
    accent: 'Accent',
    success: 'Success',
    danger: 'Danger',
};

/** Fallback palette used before the user's branding has loaded. Mirrors the backend defaults. */
export const BRANDING_FALLBACK: BrandingColors = {
    background: '#ffffff',
    primary: '#2563eb',
    secondary: '#64748b',
    accent: '#f59e0b',
    success: '#16a34a',
    danger: '#dc2626',
};

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

/** The branding palette as an ordered list of swatches for color-picker shortcuts. */
export function brandingSwatches(): BrandingSwatch[] {
    const branding = currentBranding();

    return BRANDING_KEYS.map((key) => ({
        key,
        label: BRANDING_LABELS[key],
        value: branding[key],
    }));
}
