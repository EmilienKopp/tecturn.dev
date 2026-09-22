import type { Block } from '@/types/generated';

type Mutable<T> = { -readonly [K in keyof T]: Mutable<T[K]> };

/**
 * Clones a clipboard block for pasting: fresh block and action ids, no
 * transition pinning (matching duplicateSlide), and free-position copies
 * offset a few percent so they don't land exactly on top of the original.
 */
export function cloneBlockForPaste(source: Block): Mutable<Block> {
    const block: Mutable<Block> = {
        ...structuredClone(source),
        id: `block-${crypto.randomUUID()}`,
        transition: null,
        actions:
            source.actions?.map((action) => ({
                ...structuredClone(action),
                id: `action-${crypto.randomUUID()}`,
            })) ?? [],
    };

    if (block.style.x !== null && block.style.y !== null) {
        block.style.x = String(Math.min(parseFloat(block.style.x) + 3, 90));
        block.style.y = String(Math.min(parseFloat(block.style.y) + 3, 90));
    }

    return block;
}
