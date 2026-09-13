/**
 * Slide background helpers shared by the live Presenter and the codegen export.
 *
 * Animotion's `<Slide background>` prop maps to Reveal's `data-background-color`,
 * which only accepts a solid color and silently ignores CSS gradients. Gradients
 * must go through the separate `gradient` prop (`data-background-gradient`). This
 * predicate lets both render paths route a gradient string to the right prop.
 */
export function isGradientBackground(
    background: string | null | undefined,
): boolean {
    return !!background && /\b(linear|radial|conic)-gradient\s*\(/i.test(background);
}

export type LinearGradient = {
    /** Direction in degrees. */
    angle: number;
    /** Ordered color stops (2–4 in the editor, but any count parses). */
    colors: string[];
};

/** The number of color stops the editor lets you pick. */
export const MAX_GRADIENT_STOPS = 4;
export const MIN_GRADIENT_STOPS = 2;

/** Build a `linear-gradient(...)` string from an angle and color stops. */
export function buildLinearGradient(angle: number, colors: string[]): string {
    const stops = colors.map((color) => color.trim()).filter(Boolean);

    return `linear-gradient(${angle}deg, ${stops.join(', ')})`;
}

/**
 * Parse a `linear-gradient(...)` string back into an angle and its color stops
 * so the editor can re-open one for editing. Returns null for anything that
 * isn't a linear gradient. Per-stop positions (e.g. `#fff 20%`) are dropped;
 * only the color token is kept.
 */
export function parseLinearGradient(
    value: string | null | undefined,
): LinearGradient | null {
    if (!value) {
        return null;
    }

    const match = value.trim().match(/^linear-gradient\((.*)\)$/is);

    if (!match) {
        return null;
    }

    const parts = splitTopLevelCommas(match[1]);

    if (parts.length === 0) {
        return null;
    }

    let angle = 135;
    let colorParts = parts;

    const angleMatch = parts[0].trim().match(/^(-?\d+(?:\.\d+)?)deg$/i);

    if (angleMatch) {
        angle = Number(angleMatch[1]);
        colorParts = parts.slice(1);
    }

    const colors = colorParts
        .map((part) => part.trim().replace(/\s+\d+(?:\.\d+)?%$/, '').trim())
        .filter(Boolean);

    if (colors.length === 0) {
        return null;
    }

    return { angle, colors };
}

/** Split on commas that sit at the top level, ignoring those inside `rgb(...)`. */
function splitTopLevelCommas(input: string): string[] {
    const out: string[] = [];
    let depth = 0;
    let current = '';

    for (const char of input) {
        if (char === '(') {
            depth++;
        } else if (char === ')') {
            depth--;
        }

        if (char === ',' && depth === 0) {
            out.push(current);
            current = '';
        } else {
            current += char;
        }
    }

    if (current.trim() !== '') {
        out.push(current);
    }

    return out;
}
