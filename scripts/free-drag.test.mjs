/**
 * Tests for free-mode snapping (resources/js/lib/tecturn/free-drag.ts).
 *
 * Run with: npm run test:js
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { snapAxis } from '../resources/js/lib/tecturn/free-drag.ts';

test('snaps the leading edge to the 5% margin', () => {
    const result = snapAxis(5.8, 30);
    assert.equal(result.value, 5);
    assert.equal(result.line, 5);
});

test('snaps the trailing edge to the 95% margin', () => {
    // width 30, so trailing edge sits at 95 when x = 65.
    const result = snapAxis(64.2, 30);
    assert.equal(result.value, 65);
    assert.equal(result.line, 95);
});

test('snaps the block centre to 50%', () => {
    // width 30, centre lands on 50 when x = 35.
    const result = snapAxis(34.3, 30);
    assert.equal(result.value, 35);
    assert.equal(result.line, 50);
});

test('leaves the value untouched when no edge is within the threshold', () => {
    // width 10: edges at 20 / 25 / 30, none near 5, 50 or 95.
    const result = snapAxis(20, 10);
    assert.equal(result.value, 20);
    assert.equal(result.line, null);
});

test('picks the closest snap line when several are near', () => {
    // A tiny block near centre: leading edge 49.5 (0.5 from 50) beats
    // nothing else within range.
    const result = snapAxis(49.5, 1);
    assert.equal(result.line, 50);
});

test('threshold is exclusive of gaps beyond 1.5%', () => {
    // Leading edge 6.6 is 1.6 from the 5 line: too far.
    const result = snapAxis(6.6, 30);
    assert.equal(result.line, null);
});
