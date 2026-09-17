import type { BlockStyle } from '@/types/generated';

/** Block kinds that carry sticky, per-kind style defaults captured from the Inspector. */
export type StickyBlockKind = 'text' | 'box';

/**
 * Sticky styling captured the last time a block of a given kind was styled in
 * the Inspector. New blocks of the same kind inherit these, so a user does not
 * have to open Settings > Defaults to make a tweak stick.
 */
export interface BlockStyleDefaults {
    color: string | null;
    fontFamily: string | null;
}

export interface SlideDefaults {
    background: string | null;
    fontSize: string | null;
    fontWeight: string | null;
    fontFamily: string | null;
    color: string | null;
    /** Per-kind sticky defaults captured from Inspector edits. */
    blocks: Record<StickyBlockKind, BlockStyleDefaults>;
}

/** The style properties that "stick" per kind when edited in the Inspector. */
export const STICKY_KEYS = ['color', 'fontFamily'] as const;

export const emptyBlockDefaults = (): BlockStyleDefaults => ({
    color: null,
    fontFamily: null,
});

export const defaultSlideDefaults = (): SlideDefaults => ({
    background: null,
    fontSize: null,
    fontWeight: null,
    fontFamily: null,
    color: null,
    blocks: {
        text: emptyBlockDefaults(),
        box: emptyBlockDefaults(),
    },
});

/**
 * Merge a parsed (possibly older) localStorage payload over the defaults,
 * deep-merging the per-kind `blocks` so payloads that predate that field still
 * hydrate cleanly.
 */
export function mergeStoredDefaults(parsed: unknown): SlideDefaults {
    const base = defaultSlideDefaults();

    if (!parsed || typeof parsed !== 'object') {
        return base;
    }

    const source = parsed as Partial<SlideDefaults>;

    return {
        ...base,
        ...source,
        blocks: {
            text: { ...base.blocks.text, ...(source.blocks?.text ?? {}) },
            box: { ...base.blocks.box, ...(source.blocks?.box ?? {}) },
        },
    };
}

/**
 * Capture sticky style properties from an Inspector edit into `target`. Only
 * the sticky keys (text color and font family) present in the patch are
 * written. Mutates `target.blocks[kind]`; returns whether anything changed.
 */
export function captureStickyKeys(
    target: SlideDefaults,
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
 * Resolve the style a new block should inherit. Global Settings > Defaults form
 * the base; when a kind is given, its per-kind sticky captures (color, font
 * family) take precedence.
 */
export function resolveBlockStyleDefaults(
    defaults: SlideDefaults,
    kind?: StickyBlockKind,
): Partial<BlockStyle> {
    // BlockStyle's generated properties are readonly, so accumulate into a
    // mutable shape and return it as a Partial<BlockStyle>.
    const style: { -readonly [K in keyof BlockStyle]?: BlockStyle[K] } = {};

    if (defaults.fontSize !== null) {
        style.fontSize = defaults.fontSize;
    }

    if (defaults.fontWeight !== null) {
        style.fontWeight = defaults.fontWeight;
    }

    if (defaults.fontFamily !== null) {
        style.fontFamily = defaults.fontFamily;
    }

    if (defaults.color !== null) {
        style.color = defaults.color;
    }

    if (kind) {
        const sticky = defaults.blocks[kind];

        if (sticky.fontFamily !== null) {
            style.fontFamily = sticky.fontFamily;
        }

        if (sticky.color !== null) {
            style.color = sticky.color;
        }
    }

    return style;
}
