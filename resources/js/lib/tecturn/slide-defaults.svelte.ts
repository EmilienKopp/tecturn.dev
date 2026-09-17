import {
    captureStickyKeys,
    defaultSlideDefaults,
    mergeStoredDefaults,
    resolveBlockStyleDefaults
    
    
} from '@/lib/tecturn/slide-defaults-core';
import type {SlideDefaults, StickyBlockKind} from '@/lib/tecturn/slide-defaults-core';
import type { BlockStyle } from '@/types/generated';

export type { SlideDefaults, StickyBlockKind };

const STORAGE_KEY = 'tecturn-slide-defaults';

class SlideDefaultsStore {
    private defaults = $state<SlideDefaults>(defaultSlideDefaults());

    constructor() {
        this.load();
    }

    private load(): void {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (stored) {
                this.defaults = mergeStoredDefaults(JSON.parse(stored));
            }
        } catch {
            // Ignore parse errors, use defaults
        }
    }

    private save(): void {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify($state.snapshot(this.defaults)),
            );
        } catch {
            // Ignore storage errors
        }
    }

    get(): SlideDefaults {
        return structuredClone($state.snapshot(this.defaults));
    }

    setBackground(background: string | null): void {
        this.defaults.background = background;
        this.save();
    }

    setFontSize(fontSize: string | null): void {
        this.defaults.fontSize = fontSize;
        this.save();
    }

    setFontWeight(fontWeight: string | null): void {
        this.defaults.fontWeight = fontWeight;
        this.save();
    }

    setFontFamily(fontFamily: string | null): void {
        this.defaults.fontFamily = fontFamily;
        this.save();
    }

    setColor(color: string | null): void {
        this.defaults.color = color;
        this.save();
    }

    /**
     * Capture sticky style properties from an Inspector edit so the next new
     * block of the same kind inherits them. Only the sticky keys (text color
     * and font family) present in the patch are persisted.
     */
    captureFromBlockStyle(
        kind: StickyBlockKind,
        style: Partial<BlockStyle>,
    ): void {
        if (captureStickyKeys(this.defaults, kind, style)) {
            this.save();
        }
    }

    reset(): void {
        this.defaults = defaultSlideDefaults();
        this.save();
    }

    /**
     * Returns a partial BlockStyle with the style properties that new blocks
     * should inherit. Global Settings > Defaults form the base; when a kind is
     * given, its per-kind sticky captures (color, font family) take precedence.
     */
    getBlockStyleDefaults(kind?: StickyBlockKind): Partial<BlockStyle> {
        return resolveBlockStyleDefaults($state.snapshot(this.defaults), kind);
    }
}

export const slideDefaults = new SlideDefaultsStore();
