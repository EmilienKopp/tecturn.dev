/**
 * Tests for the last-used style core
 * (resources/js/lib/tecturn/last-used-style-core.ts).
 *
 * Run with: npm run test:js
 *
 * The runed store (last-used-style.svelte.ts) is a thin wrapper over these
 * pure functions, so the branding-base + last-used-override resolution is
 * tested here without needing a Svelte runtime.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    captureStickyKeys,
    emptyLastUsed,
    mergeStoredLastUsed,
    resolveNewBlockStyle,
} from '../resources/js/lib/tecturn/last-used-style-core.ts';

const branding = (overrides = {}) => ({
    background: '#ffffff',
    primary: '#2563eb',
    secondary: '#64748b',
    accent: '#f59e0b',
    success: '#16a34a',
    danger: '#dc2626',
    fontFamily: null,
    fontSize: null,
    fontWeight: null,
    ...overrides,
});

test('branding primary is the default text color for new blocks', () => {
    const style = resolveNewBlockStyle(branding(), emptyLastUsed(), 'text');

    assert.equal(style.color, '#2563eb');
    assert.equal(style.fontFamily, undefined);
});

test('branding typography slots seed new blocks when set', () => {
    const style = resolveNewBlockStyle(
        branding({ fontFamily: 'Lora', fontSize: '2rem', fontWeight: 'bold' }),
        emptyLastUsed(),
        'text',
    );

    assert.equal(style.fontFamily, 'Lora');
    assert.equal(style.fontSize, '2rem');
    assert.equal(style.fontWeight, 'bold');
});

test('last-used captures override branding for their kind only', () => {
    const lastUsed = emptyLastUsed();
    captureStickyKeys(lastUsed, 'text', {
        color: '#ff0000',
        fontSize: '3rem',
    });

    const text = resolveNewBlockStyle(
        branding({ fontSize: '2rem' }),
        lastUsed,
        'text',
    );
    const box = resolveNewBlockStyle(
        branding({ fontSize: '2rem' }),
        lastUsed,
        'box',
    );

    assert.equal(text.color, '#ff0000');
    assert.equal(text.fontSize, '3rem');
    assert.equal(box.color, '#2563eb');
    assert.equal(box.fontSize, '2rem');
});

test('capture only writes sticky keys present in the patch', () => {
    const lastUsed = emptyLastUsed();
    const changed = captureStickyKeys(lastUsed, 'box', {
        fontWeight: 'semibold',
    });

    assert.equal(changed, true);
    assert.equal(lastUsed.blocks.box.fontWeight, 'semibold');
    assert.equal(lastUsed.blocks.box.color, null);

    assert.equal(
        captureStickyKeys(lastUsed, 'box', { borderColor: '#000000' }),
        false,
    );
});

test('a capture explicitly cleared to null falls back to branding', () => {
    const lastUsed = emptyLastUsed();
    captureStickyKeys(lastUsed, 'text', { color: '#ff0000' });
    captureStickyKeys(lastUsed, 'text', { color: null });

    const style = resolveNewBlockStyle(branding(), lastUsed, 'text');

    assert.equal(style.color, '#2563eb');
});

test('mergeStoredLastUsed keeps only known keys and survives junk', () => {
    assert.deepEqual(mergeStoredLastUsed('junk'), emptyLastUsed());
    assert.deepEqual(mergeStoredLastUsed(null), emptyLastUsed());

    const merged = mergeStoredLastUsed({
        blocks: {
            text: { color: '#123456', bogus: 'x', fontSize: 42 },
        },
    });

    assert.equal(merged.blocks.text.color, '#123456');
    assert.equal(merged.blocks.text.fontSize, null);
    assert.equal('bogus' in merged.blocks.text, false);
});
