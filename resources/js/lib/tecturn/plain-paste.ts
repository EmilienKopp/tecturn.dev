/**
 * Plain-text paste for the inline-format contenteditables (text and box
 * blocks). Browsers serialize computed styles into the copied HTML (e.g.
 * `<span style="font-size: 24.576px">`), and the sanitizer's span allowlist
 * would keep those on paste, freezing a pixel size that only looks right in
 * the editor. Pasting the clipboard's plain text instead drops every carried
 * span and style; line breaks come through as <br>, matching the Enter
 * handling in the block views.
 */

/** Normalize clipboard text into lines, treating \r\n and \r as \n. */
export function clipboardLines(text: string): string[] {
    return text.replace(/\r\n?/g, '\n').split('\n');
}

export function pastePlainText(event: ClipboardEvent): void {
    event.preventDefault();

    const text = event.clipboardData?.getData('text/plain') ?? '';

    if (!text) {
        return;
    }

    clipboardLines(text).forEach((line, index) => {
        if (index > 0) {
            document.execCommand('insertLineBreak');
        }

        if (line) {
            document.execCommand('insertText', false, line);
        }
    });
}
