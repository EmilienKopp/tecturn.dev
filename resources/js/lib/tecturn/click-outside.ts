import type { Action } from 'svelte/action';

/**
 * Calls the handler on any pointerdown outside the node, document-wide.
 * Listens in the capture phase so a stopPropagation between the target and
 * the document (e.g. the canvases' own click handlers) can't swallow it.
 */
export const clickOutside: Action<HTMLElement, () => void> = (
    node,
    onOutside,
) => {
    let handler = onOutside;

    const onPointerDown = (event: PointerEvent) => {
        if (!node.contains(event.target as Node)) {
            handler();
        }
    };

    document.addEventListener('pointerdown', onPointerDown, true);

    return {
        update(next: () => void) {
            handler = next;
        },
        destroy() {
            document.removeEventListener('pointerdown', onPointerDown, true);
        },
    };
};
