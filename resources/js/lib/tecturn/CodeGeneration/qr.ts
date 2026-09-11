import QRCode from 'qrcode';
import { escapeAttribute } from './support.ts';

/**
 * The single source of truth for turning a URL into a QR code. Used by the
 * codegen export, the editor canvas, and the live presenter alike, so a QR
 * looks identical everywhere and the exported embed stays self-contained (the
 * SVG is inlined, no runtime script or network fetch).
 */

export type QrSize = 'small' | 'medium' | 'large';

/**
 * On-slide width per size preset, in `cqw` (container-query width), so the QR
 * scales with the stage exactly like text and code do (see scaling.ts). The
 * stage is calibrated at 1000px wide, so 26cqw == 260px on that reference. This
 * keeps the QR the same relative size on the editor canvas and the live stage.
 */
export const QR_SIZE_CQW: Record<QrSize, number> = {
    small: 16,
    medium: 26,
    large: 38,
};

export function normalizeQrSize(value: string | null | undefined): QrSize {
    return value === 'small' || value === 'large' ? value : 'medium';
}

/**
 * Build a standalone SVG for a URL. Deterministic and script-free so it renders
 * in the export embed and the presenter without any runtime QR library. A white
 * quiet zone (the standard 4-module margin) keeps scanners reliable on the lit
 * slide canvas.
 */
export function qrToSvg(url: string, opts: { title?: string } = {}): string {
    const qr = QRCode.create(url, { errorCorrectionLevel: 'M' });
    const count = qr.modules.size;
    const data = qr.modules.data;
    const margin = 4;
    const dim = count + margin * 2;

    let rects = '';
    for (let row = 0; row < count; row++) {
        for (let col = 0; col < count; col++) {
            if (data[row * count + col]) {
                rects += `<rect x="${col + margin}" y="${row + margin}" width="1" height="1"/>`;
            }
        }
    }

    const label = opts.title
        ? ` role="img" aria-label="${escapeAttribute(opts.title)}"`
        : ' aria-hidden="true"';

    return (
        `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${dim} ${dim}"` +
        ` shape-rendering="crispEdges" style="width: 100%; height: auto; display: block;"${label}>` +
        `<rect width="${dim}" height="${dim}" fill="#ffffff"/>` +
        `<g fill="#0a0a0a">${rects}</g>` +
        `</svg>`
    );
}
