/**
 * Tests for the sticky slide-defaults core
 * (resources/js/lib/tecturn/slide-defaults-core.ts).
 *
 * Run with: npm run test:js
 *
 * The runed store (slide-defaults.svelte.ts) is a thin wrapper over these pure
 * functions, so the sticky per-kind precedence logic is tested here without
 * needing a Svelte runtime.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    captureStickyKeys,
    defaultSlideDefaults,
    mergeStoredDefaults,
    resolveBlockStyleDefaults,
} from '../resources/js/lib/tecturn/slide-defaults-core.ts';

test('resolveBlockStyleDefaults returns only globally-set properties', () => {
    const defaults = defaultSlideDefaults();
    defaults.fontSize = '2rem';
    defaults.color = '#111111';

    assert.deepEqual(resolveBlockStyleDefaults(defaults), {
        fontSize: '2rem',
        color: '#111111',
    });
});

test('per-kind sticky color and font family override the global defaults', () => {
    const defaults = defaultSlideDefaults();
    defaults.fontFamily = 'Inter';
    defaults.color = '#000000';
    defaults.blocks.text.color = '#ff0000';
    defaults.blocks.text.fontFamily = 'Lora';

    const resolved = resolveBlockStyleDefaults(defaults, 'text');

    assert.equal(resolved.color, '#ff0000');
    assert.equal(resolved.fontFamily, 'Lora');
});

test('sticky defaults are scoped per kind', () => {
    const defaults = defaultSlideDefaults();
    defaults.blocks.text.color = '#ff0000';

    assert.equal(resolveBlockStyleDefaults(defaults, 'text').color, '#ff0000');
    // A box has its own bucket, so it does not inherit the text color.
    assert.equal(resolveBlockStyleDefaults(defaults, 'box').color, undefined);
});

test('captureStickyKeys only persists sticky keys present in the patch', () => {
    const defaults = defaultSlideDefaults();

    const changed = captureStickyKeys(defaults, 'text', {
        color: '#123456',
        fontFamily: 'Anton',
        // Non-sticky keys must be ignored.
        fontSize: '3rem',
        borderColor: '#eeeeee',
    });

    assert.equal(changed, true);
    assert.equal(defaults.blocks.text.color, '#123456');
    assert.equal(defaults.blocks.text.fontFamily, 'Anton');
    // fontSize is not sticky, so the global default is untouched.
    assert.equal(defaults.fontSize, null);
});

test('captureStickyKeys reports no change when no sticky keys are present', () => {
    const defaults = defaultSlideDefaults();

    const changed = captureStickyKeys(defaults, 'box', {
        fontSize: '1rem',
        borderColor: '#000000',
    });

    assert.equal(changed, false);
    assert.equal(defaults.blocks.box.color, null);
});

test('captureStickyKeys clears a sticky value when the patch sets it to null', () => {
    const defaults = defaultSlideDefaults();
    defaults.blocks.text.color = '#abcdef';

    captureStickyKeys(defaults, 'text', { color: null });

    assert.equal(defaults.blocks.text.color, null);
});

test('mergeStoredDefaults hydrates legacy payloads without a blocks field', () => {
    const legacy = { background: '#fff', fontSize: '2rem', color: '#000' };

    const merged = mergeStoredDefaults(legacy);

    assert.equal(merged.background, '#fff');
    assert.equal(merged.fontSize, '2rem');
    assert.deepEqual(merged.blocks.text, { color: null, fontFamily: null });
    assert.deepEqual(merged.blocks.box, { color: null, fontFamily: null });
});

test('mergeStoredDefaults deep-merges a partial blocks payload', () => {
    const stored = {
        blocks: { text: { color: '#ff0000' } },
    };

    const merged = mergeStoredDefaults(stored);

    assert.equal(merged.blocks.text.color, '#ff0000');
    assert.equal(merged.blocks.text.fontFamily, null);
    assert.deepEqual(merged.blocks.box, { color: null, fontFamily: null });
});

test('mergeStoredDefaults falls back to defaults for junk input', () => {
    assert.deepEqual(mergeStoredDefaults(null), defaultSlideDefaults());
    assert.deepEqual(mergeStoredDefaults('nope'), defaultSlideDefaults());
});
