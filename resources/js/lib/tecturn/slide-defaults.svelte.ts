import type { BlockStyle } from '@/types/generated';

export interface SlideDefaults {
    background: string | null;
    fontSize: string | null;
    fontWeight: string | null;
    fontFamily: string | null;
    color: string | null;
}

const STORAGE_KEY = 'tecturn-slide-defaults';

const DEFAULT_VALUES: SlideDefaults = {
    background: null,
    fontSize: null,
    fontWeight: null,
    fontFamily: null,
    color: null,
};

class SlideDefaultsStore {
    private defaults = $state<SlideDefaults>({ ...DEFAULT_VALUES });

    constructor() {
        this.load();
    }

    private load(): void {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (stored) {
                const parsed = JSON.parse(stored);
                this.defaults = { ...DEFAULT_VALUES, ...parsed };
            }
        } catch {
            // Ignore parse errors, use defaults
        }
    }

    private save(): void {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.defaults));
        } catch {
            // Ignore storage errors
        }
    }

    get(): SlideDefaults {
        return { ...this.defaults };
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

    reset(): void {
        this.defaults = { ...DEFAULT_VALUES };
        this.save();
    }

    /**
     * Returns a partial BlockStyle with only the style properties that have defaults set.
     * This can be applied to new blocks to inherit slide defaults.
     */
    getBlockStyleDefaults(): Partial<BlockStyle> {
        const style: Partial<BlockStyle> = {};

        if (this.defaults.fontSize !== null) {
            style.fontSize = this.defaults.fontSize;
        }

        if (this.defaults.fontWeight !== null) {
            style.fontWeight = this.defaults.fontWeight;
        }

        if (this.defaults.fontFamily !== null) {
            style.fontFamily = this.defaults.fontFamily;
        }

        if (this.defaults.color !== null) {
            style.color = this.defaults.color;
        }

        return style;
    }
}

export const slideDefaults = new SlideDefaultsStore();
