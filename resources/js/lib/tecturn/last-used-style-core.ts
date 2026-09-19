import type { BrandingColors } from '@/types/auth';
import type { BlockStyle } from '@/types/generated';

/**
 * The "last used" style system: branding is the single source of defaults
 * (primary = text color, typography slots seed new text blocks), and any
 * style picked in the Inspector is captured per block kind as a transient
 * override so it does not have to be re-picked block after block. New blocks
 * resolve last-used first, then branding.
 */

/** Block kinds that carry last-used style overrides captured from the Inspector. */
export type StickyBlockKind = 'text' | 'box';

/** The style properties that stick per kind when edited in the Inspector. */
export const STICKY_KEYS = [
    'color',
    'fontFamily',
    'fontSize',
    'fontWeight',
] as const;

export type StickyKey = (typeof STICKY_KEYS)[number];

export type LastUsedBlockStyle = Record<StickyKey, string | null>;

export interface LastUsedStyles {
    blocks: Record<StickyBlockKind, LastUsedBlockStyle>;
}

export const emptyLastUsedBlockStyle = (): LastUsedBlockStyle => ({
    color: null,
    fontFamily: null,
    fontSize: null,
    fontWeight: null,
});

export const emptyLastUsed = (): LastUsedStyles => ({
    blocks: {
        text: emptyLastUsedBlockStyle(),
        box: emptyLastUsedBlockStyle(),
    },
});

/**
 * Merge a parsed (possibly older or malformed) localStorage payload over the
 * empty shape, keeping only known keys.
 */
export function mergeStoredLastUsed(parsed: unknown): LastUsedStyles {
    const base = emptyLastUsed();

    if (!parsed || typeof parsed !== 'object') {
        return base;
    }

    const source = parsed as Partial<LastUsedStyles>;

    for (const kind of ['text', 'box'] as const) {
        const stored = source.blocks?.[kind];

        if (!stored || typeof stored !== 'object') {
            continue;
        }

        for (const key of STICKY_KEYS) {
            const value = stored[key];

            if (typeof value === 'string' || value === null) {
                base.blocks[kind][key] = value;
            }
        }
    }

    return base;
}

/**
 * Capture sticky style properties from an Inspector edit into `target`. Only
 * sticky keys present in the patch are written. Mutates
 * `target.blocks[kind]`; returns whether anything changed.
 */
export function captureStickyKeys(
    target: LastUsedStyles,
    kind: StickyBlockKind,
    style: Partial<BlockStyle>,
): boolean {
    let changed = false;

    for (const key of STICKY_KEYS) {
        if (key in style) {
            target.blocks[kind][key] = style[key] ?? null;
            changed = true;
        }
    }

    return changed;
}

/**
 * The style defaults branding contributes to a new block: primary as the
 * text color plus any set typography slots.
 */
export function brandingBlockStyle(
    branding: BrandingColors,
): Partial<BlockStyle> {
    const style: { -readonly [K in keyof BlockStyle]?: BlockStyle[K] } = {
        color: branding.primary,
    };

    if (branding.fontFamily !== null) {
        style.fontFamily = branding.fontFamily;
    }

    if (branding.fontSize !== null) {
        style.fontSize = branding.fontSize;
    }

    if (branding.fontWeight !== null) {
        style.fontWeight = branding.fontWeight;
    }

    return style;
}

/**
 * Resolve the style a new block should inherit: branding forms the base, and
 * the kind's last-used captures take precedence where set.
 */
export function resolveNewBlockStyle(
    branding: BrandingColors,
    lastUsed: LastUsedStyles,
    kind?: StickyBlockKind,
): Partial<BlockStyle> {
    const style: { -readonly [K in keyof BlockStyle]?: BlockStyle[K] } =
        brandingBlockStyle(branding);

    if (kind) {
        for (const key of STICKY_KEYS) {
            const value = lastUsed.blocks[kind][key];

            if (value !== null) {
                style[key] = value;
            }
        }
    }

    return style;
}
