/**
 * Pointer-driven drag/resize helper for the free-form slide layout.
 *
 * Positions are stored as percentages of the slide container so the editor
 * canvas, live presenter, and exported deck (all 16:9 but different pixel
 * sizes) render identically. This helper reports movement as a percentage
 * delta from the pointerdown origin; callers add it to the block's starting
 * value and clamp.
 */

/** Fallback position/size (percent) for blocks whose fields are still null. */
export const FREE_DEFAULTS = {
    x: 10,
    y: 10,
    width: 30,
} as const;

/** Pointer movement (px) tolerated before a press counts as a drag. */
const MOVE_THRESHOLD = 4;

export function clampPercent(value: number, min: number, max: number): number {
    return Math.min(max, Math.max(min, value));
}

export function round2(value: number): number {
    return Math.round(value * 100) / 100;
}

/** Snap lines (percent) a block gravitates toward: the two 5% margins and centre. */
export const SNAP_LINES = [5, 50, 95] as const;

/** Max gap (percent) between a block edge and a snap line for it to catch. */
const SNAP_THRESHOLD = 1.5;

export type SnapResult = {
    /** Snapped leading-edge position, or the original value when nothing caught. */
    value: number;
    /** The snap line that caught (for drawing a guide), or null. */
    line: number | null;
};

/**
 * Snap one axis of a block toward the centre (50%) or the 5%/95% margins.
 *
 * `start` is the block's leading-edge position (left or top) and `size` its
 * extent (width or height), both in percent. The leading edge, centre, and
 * trailing edge are each tested against every snap line; the closest match
 * within the threshold wins and the returned `value` is the leading-edge
 * position that lands the matched edge on that line.
 */
export function snapAxis(start: number, size: number): SnapResult {
    let best: { value: number; line: number; distance: number } | null = null;

    for (const line of SNAP_LINES) {
        // Offsets from the leading edge to: leading edge, centre, trailing edge.
        for (const offset of [0, size / 2, size]) {
            const distance = Math.abs(start + offset - line);

            if (
                distance <= SNAP_THRESHOLD &&
                (best === null || distance < best.distance)
            ) {
                best = { value: line - offset, line, distance };
            }
        }
    }

    return best === null
        ? { value: start, line: null }
        : { value: best.value, line: best.line };
}

type DragOptions = {
    /** Element whose size defines 100% for the percentage math. */
    container: HTMLElement;
    /** Cumulative delta from drag start, in percent of the container. */
    onMove: (deltaXPercent: number, deltaYPercent: number) => void;
    onEnd?: () => void;
};

/**
 * Begin a pointer drag from a `pointerdown` event. Captures the pointer on the
 * initiating element and streams percentage deltas until release. Plain clicks
 * (movement under the threshold) never fire `onMove`, so selection/editing on
 * the underlying block is preserved.
 */
export function startPointerDrag(
    event: PointerEvent,
    { container, onMove, onEnd }: DragOptions,
): void {
    event.preventDefault();
    event.stopPropagation();

    const target = event.currentTarget as HTMLElement;
    const rect = container.getBoundingClientRect();
    const startX = event.clientX;
    const startY = event.clientY;
    let moved = false;

    target.setPointerCapture(event.pointerId);

    const handleMove = (e: PointerEvent): void => {
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        if (
            !moved &&
            Math.abs(dx) < MOVE_THRESHOLD &&
            Math.abs(dy) < MOVE_THRESHOLD
        ) {
            return;
        }

        moved = true;
        onMove((dx / rect.width) * 100, (dy / rect.height) * 100);
    };

    const handleUp = (): void => {
        target.releasePointerCapture(event.pointerId);
        target.removeEventListener('pointermove', handleMove);
        target.removeEventListener('pointerup', handleUp);
        onEnd?.();
    };

    target.addEventListener('pointermove', handleMove);
    target.addEventListener('pointerup', handleUp);
}
