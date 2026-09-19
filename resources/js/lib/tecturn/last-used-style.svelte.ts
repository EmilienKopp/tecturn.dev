import { currentBranding } from '@/lib/tecturn/branding';
import {
    captureStickyKeys,
    emptyLastUsed,
    emptyLastUsedBlockStyle,
    mergeStoredLastUsed,
    resolveNewBlockStyle,
} from '@/lib/tecturn/last-used-style-core';
import type {
    LastUsedStyles,
    StickyBlockKind,
} from '@/lib/tecturn/last-used-style-core';
import type { BlockStyle } from '@/types/generated';

export type { LastUsedStyles, StickyBlockKind };

const storageKey = (presentationId: number): string =>
    `tecturn-last-used:${presentationId}`;

/**
 * Per-presentation "last used" style overrides. Branding is the default;
 * whatever the user picks in the Inspector is captured here (per block kind)
 * so the next block starts from it. Scoped to a presentation via `scope()`,
 * called when the editor mounts.
 */
class LastUsedStyleStore {
    private presentationId: number | null = null;
    private styles = $state<LastUsedStyles>(emptyLastUsed());

    /** Load (or start) the overrides for the given presentation. */
    scope(presentationId: number): void {
        this.presentationId = presentationId;
        this.styles = emptyLastUsed();

        try {
            const stored = localStorage.getItem(storageKey(presentationId));

            if (stored) {
                this.styles = mergeStoredLastUsed(JSON.parse(stored));
            }
        } catch {
            // Ignore parse errors, start empty
        }
    }

    private save(): void {
        if (this.presentationId === null) {
            return;
        }

        try {
            localStorage.setItem(
                storageKey(this.presentationId),
                JSON.stringify($state.snapshot(this.styles)),
            );
        } catch {
            // Ignore storage errors
        }
    }

    /**
     * Capture sticky style properties from an Inspector edit so the next new
     * block of the same kind starts from them.
     */
    captureFromBlockStyle(
        kind: StickyBlockKind,
        style: Partial<BlockStyle>,
    ): void {
        if (captureStickyKeys(this.styles, kind, style)) {
            this.save();
        }
    }

    /** Drop the captured overrides for a kind, falling back to branding. */
    clearKind(kind: StickyBlockKind): void {
        this.styles.blocks[kind] = emptyLastUsedBlockStyle();
        this.save();
    }

    /**
     * The style a new block should inherit: branding as the base, the kind's
     * last-used captures on top.
     */
    resolveNewBlockStyle(kind?: StickyBlockKind): Partial<BlockStyle> {
        return resolveNewBlockStyle(
            currentBranding(),
            $state.snapshot(this.styles),
            kind,
        );
    }
}

export const lastUsedStyle = new LastUsedStyleStore();
