/**
 * Tests for the plain-text paste line normalization
 * (resources/js/lib/tecturn/plain-paste.ts).
 *
 * Run with: npm run test:js
 *
 * pastePlainText itself is DOM-bound (execCommand); the clipboard text
 * normalization it relies on is pure and tested here.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { clipboardLines } from '../resources/js/lib/tecturn/plain-paste.ts';

test('splits on \\n', () => {
    assert.deepEqual(clipboardLines('one\ntwo\nthree'), [
        'one',
        'two',
        'three',
    ]);
});

test('normalizes Windows \\r\\n and bare \\r line endings', () => {
    assert.deepEqual(clipboardLines('one\r\ntwo\rthree'), [
        'one',
        'two',
        'three',
    ]);
});

test('keeps empty lines so blank rows survive the paste', () => {
    assert.deepEqual(clipboardLines('one\n\ntwo'), ['one', '', 'two']);
});

test('single line passes through untouched', () => {
    assert.deepEqual(clipboardLines('just text'), ['just text']);
});
