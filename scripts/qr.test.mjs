/**
 * Tests for the QR generator (resources/js/lib/tecturn/CodeGeneration/qr.ts)
 * and the codegen QR block renderer.
 *
 * Run with: npm run test:js
 *
 * Node strips the TypeScript on import natively, the same way present.mjs runs
 * the codegen, so no bundler or extra dependency is involved.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { QrRenderer } from '../resources/js/lib/tecturn/CodeGeneration/plugins/blocks.ts';
import {
    QR_SIZE_CQW,
    normalizeQrSize,
    qrToSvg,
} from '../resources/js/lib/tecturn/CodeGeneration/qr.ts';

test('qrToSvg emits a self-contained svg with modules', () => {
    const svg = qrToSvg('https://example.com');

    assert.match(svg, /^<svg[^>]*viewBox="0 0 \d+ \d+"/);
    assert.ok(svg.includes('<rect'), 'has module rects');
    assert.ok(svg.includes('fill="#ffffff"'), 'has white quiet-zone background');
    assert.ok(!svg.includes('<script'), 'no runtime script');
});

test('qrToSvg is deterministic and url-sensitive', () => {
    assert.equal(qrToSvg('https://a.example'), qrToSvg('https://a.example'));
    assert.notEqual(qrToSvg('https://a.example'), qrToSvg('https://b.example'));
});

test('qrToSvg sets an aria-label only when a title is given', () => {
    assert.match(
        qrToSvg('https://x.example', { title: 'https://x.example' }),
        /aria-label="https:\/\/x\.example"/,
    );
    assert.match(qrToSvg('https://x.example'), /aria-hidden="true"/);
});

test('normalizeQrSize falls back to medium', () => {
    assert.equal(normalizeQrSize('small'), 'small');
    assert.equal(normalizeQrSize('large'), 'large');
    assert.equal(normalizeQrSize('medium'), 'medium');
    assert.equal(normalizeQrSize(null), 'medium');
    assert.equal(normalizeQrSize('bogus'), 'medium');
});

test('QrRenderer wraps the svg at the preset width', () => {
    const renderer = new QrRenderer();
    const block = { type: 'qr', src: 'https://example.com', alt: 'large' };

    const html = renderer.render(block, 0);

    assert.ok(
        html.includes(`width: ${QR_SIZE_CQW.large}cqw`),
        'uses large preset',
    );
    assert.ok(html.includes('<svg'), 'inlines the svg');
});

test('QrRenderer renders nothing visible without a url', () => {
    const renderer = new QrRenderer();
    const html = renderer.render({ type: 'qr', src: '', alt: 'medium' }, 0);

    assert.ok(!html.includes('<svg'), 'no svg for empty url');
    assert.ok(html.includes('display: none'), 'collapses to nothing');
});
