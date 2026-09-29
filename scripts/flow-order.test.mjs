/**
 * Tests for slidesInNavOrder (resources/js/lib/tecturn/flow-compiler.ts): the
 * nav chain is the source of truth for slide order.
 *
 * Run with: npm run test:js
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { slidesInNavOrder } from '../resources/js/lib/tecturn/flow-compiler.ts';

function content(...slideIds) {
    return { slides: slideIds.map((id) => ({ id })) };
}

function slideNode(slideId, y = 0) {
    return {
        id: `node-${slideId}`,
        type: 'slide',
        position: { x: 0, y },
        data: { slideId },
    };
}

function navEdge(fromSlide, toSlide) {
    return {
        id: `edge-${fromSlide}-${toSlide}`,
        source: `node-${fromSlide}`,
        target: `node-${toSlide}`,
        label: null,
    };
}

function flow(nodes, edges = []) {
    return { version: '1.0', nodes, edges };
}

test('an unwired deck keeps its content order', () => {
    const result = slidesInNavOrder(
        content('a', 'b', 'c'),
        flow([slideNode('a'), slideNode('b'), slideNode('c')]),
    );

    assert.deepEqual(result, ['a', 'b', 'c']);
});

test('a chain that matches content order returns the same order', () => {
    const result = slidesInNavOrder(
        content('a', 'b', 'c'),
        flow(
            [slideNode('a'), slideNode('b'), slideNode('c')],
            [navEdge('a', 'b'), navEdge('b', 'c')],
        ),
    );

    assert.deepEqual(result, ['a', 'b', 'c']);
});

test('the chain, not the array, dictates order', () => {
    // Array is a, b, c but edges wire c -> a -> b.
    const result = slidesInNavOrder(
        content('a', 'b', 'c'),
        flow(
            [slideNode('a'), slideNode('b'), slideNode('c')],
            [navEdge('c', 'a'), navEdge('a', 'b')],
        ),
    );

    assert.deepEqual(result, ['c', 'a', 'b']);
});

test('a slide with no nav edges holds its position while the chain reorders', () => {
    // b is unwired; a and c form a chain c -> a. b stays in slot 2.
    const result = slidesInNavOrder(
        content('a', 'b', 'c'),
        flow(
            [slideNode('a'), slideNode('b'), slideNode('c')],
            [navEdge('c', 'a')],
        ),
    );

    // Slots 1 and 3 hold chain slides (walk order c, a); slot 2 keeps b.
    assert.deepEqual(result, ['c', 'b', 'a']);
});

test('a cycle falls back to keeping every slide in place', () => {
    const result = slidesInNavOrder(
        content('a', 'b', 'c'),
        flow(
            [slideNode('a'), slideNode('b'), slideNode('c')],
            [navEdge('a', 'b'), navEdge('b', 'a')],
        ),
    );

    assert.deepEqual(result, ['a', 'b', 'c']);
});

test('every slide appears exactly once', () => {
    const result = slidesInNavOrder(
        content('a', 'b', 'c', 'd'),
        flow(
            [
                slideNode('a'),
                slideNode('b'),
                slideNode('c'),
                slideNode('d'),
            ],
            [navEdge('d', 'c'), navEdge('c', 'b'), navEdge('b', 'a')],
        ),
    );

    assert.deepEqual([...result].sort(), ['a', 'b', 'c', 'd']);
    assert.deepEqual(result, ['d', 'c', 'b', 'a']);
});
