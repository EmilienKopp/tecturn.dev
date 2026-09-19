/**
 * Tests for the block clipboard clone logic
 * (resources/js/lib/tecturn/block-clipboard.ts).
 *
 * Run with: npm run test:js
 *
 * The runed EditorState.copyBlock/pasteBlock are thin wrappers over
 * cloneBlockForPaste, so the id-refresh and offset rules are tested here
 * without needing a Svelte runtime.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { cloneBlockForPaste } from '../resources/js/lib/tecturn/block-clipboard.ts';

const makeBlock = (overrides = {}) => ({
    id: 'block-source',
    type: 'text',
    content: 'Hello',
    style: {
        fontSize: '2rem',
        fontWeight: null,
        fontFamily: null,
        color: '#ff0000',
        borderColor: null,
        backgroundColor: null,
        gridColumn: null,
        gridRow: null,
        x: null,
        y: null,
        width: null,
        height: null,
    },
    transition: { kind: 'fade' },
    lang: null,
    src: null,
    alt: null,
    actions: [],
    ...overrides,
});

test('clone gets a fresh block id and keeps content and style', () => {
    const source = makeBlock();
    const clone = cloneBlockForPaste(source);

    assert.notEqual(clone.id, source.id);
    assert.match(clone.id, /^block-/);
    assert.equal(clone.content, 'Hello');
    assert.equal(clone.style.fontSize, '2rem');
    assert.equal(clone.style.color, '#ff0000');
});

test('clone does not carry transition pinning', () => {
    const clone = cloneBlockForPaste(makeBlock());

    assert.equal(clone.transition, null);
});

test('actions are deep-copied with fresh ids', () => {
    const source = makeBlock({
        type: 'code',
        actions: [
            { id: 'action-1', code: 'const a = 1;', lines: '1' },
            { id: 'action-2', code: 'const b = 2;', lines: '2' },
        ],
    });
    const clone = cloneBlockForPaste(source);

    assert.equal(clone.actions.length, 2);
    assert.notEqual(clone.actions[0].id, 'action-1');
    assert.notEqual(clone.actions[1].id, 'action-2');
    assert.equal(clone.actions[0].code, 'const a = 1;');

    clone.actions[0].code = 'mutated';
    assert.equal(source.actions[0].code, 'const a = 1;');
});

test('missing actions normalise to an empty array', () => {
    const clone = cloneBlockForPaste(makeBlock({ actions: undefined }));

    assert.deepEqual(clone.actions, []);
});

test('free-position blocks paste offset by 3, clamped to 90', () => {
    const clone = cloneBlockForPaste(
        makeBlock({
            style: { ...makeBlock().style, x: '10', y: '89' },
        }),
    );

    assert.equal(clone.style.x, '13');
    assert.equal(clone.style.y, '90');
});

test('non-free blocks keep their grid placement untouched', () => {
    const clone = cloneBlockForPaste(
        makeBlock({
            style: {
                ...makeBlock().style,
                gridColumn: '2 / 4',
                gridRow: '1 / 2',
            },
        }),
    );

    assert.equal(clone.style.gridColumn, '2 / 4');
    assert.equal(clone.style.gridRow, '1 / 2');
    assert.equal(clone.style.x, null);
    assert.equal(clone.style.y, null);
});

test('cloning is deep: mutating the clone style leaves the source alone', () => {
    const source = makeBlock();
    const clone = cloneBlockForPaste(source);

    clone.style.color = '#00ff00';
    clone.content = 'Changed';

    assert.equal(source.style.color, '#ff0000');
    assert.equal(source.content, 'Hello');
});
