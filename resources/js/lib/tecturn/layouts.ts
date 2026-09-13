import type { SlideLayout } from '@/types/generated';

export type LayoutDefinition = {
    label: string;
    slots: string[];
    /** Tailwind classes for the slide-level grid/flex container. */
    containerClass: string;
    /** Optional per-slot classes (keyed by slot name). */
    slotClass?: Record<string, string>;
};

// Only Full, Center, and Free are enabled for now; the richer multi-slot
// layouts are intentionally disabled until they're finished. The map is cast to
// the full `Record` so callers stay simply typed, but any slide that somehow
// carries a disabled layout must be resolved through `layoutDefinition()` below,
// which falls back to Center rather than crashing on an undefined lookup.
export const layoutDefinitions = {
    full: {
        label: 'Full',
        slots: ['main'],
        containerClass: 'grid h-full grid-cols-1',
    },
    center: {
        label: 'Center',
        slots: ['main'],
        containerClass: 'flex h-full items-center justify-center',
    },
    free: {
        label: 'Free',
        slots: ['main'],
        containerClass: 'relative h-full',
    },
} as Record<SlideLayout, LayoutDefinition>;

/** Resolve a layout to its definition, falling back to Center when disabled. */
export function layoutDefinition(layout: SlideLayout): LayoutDefinition {
    return layoutDefinitions[layout] ?? layoutDefinitions.center;
}

export const availableLayouts: SlideLayout[] = ['full', 'center', 'free'];
