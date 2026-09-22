/**
 * Helpers for external presentation sources (PDF / Google Slides).
 */

/**
 * Turn whatever Google Slides URL the presenter pasted (a `/edit`, `/pub`,
 * `/view` or `/present` link) into the embeddable `/embed` form. Returns null
 * for empty input, and leaves an already-embed URL untouched.
 */
export function googleSlidesEmbedUrl(url: string | null | undefined): string | null {
    if (!url) {
        return null;
    }

    if (/\/embed(\?|$)/.test(url)) {
        return url;
    }

    const base = url.replace(
        /\/(edit|pub|view|present|htmlpresent)(\?[^#]*)?(#.*)?$/,
        '',
    );

    return `${base}/embed?start=false&loop=false&delayms=5000`;
}
