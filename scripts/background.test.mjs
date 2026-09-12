/**
 * Tests for the slide background helper (resources/js/lib/tecturn/background.ts).
 *
 * Run with: npm run test:js
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    buildLinearGradient,
    isGradientBackground,
    parseLinearGradient,
} from '../resources/js/lib/tecturn/background.ts';

test('isGradientBackground detects CSS gradient functions', () => {
    assert.equal(
        isGradientBackground('linear-gradient(135deg, #0f2027, #203a43)'),
        true,
    );
    assert.equal(isGradientBackground('radial-gradient(#fff, #000)'), true);
    assert.equal(isGradientBackground('conic-gradient(from 0deg, red, blue)'), true);
    assert.equal(isGradientBackground('LINEAR-GRADIENT(#fff, #000)'), true);
});

test('isGradientBackground is false for solid colors and empty input', () => {
    assert.equal(isGradientBackground('#0b1021'), false);
    assert.equal(isGradientBackground('rebeccapurple'), false);
    assert.equal(isGradientBackground('rgb(11, 16, 33)'), false);
    assert.equal(isGradientBackground(null), false);
    assert.equal(isGradientBackground(undefined), false);
    assert.equal(isGradientBackground(''), false);
});

test('buildLinearGradient joins angle and stops, dropping blanks', () => {
    assert.equal(
        buildLinearGradient(135, ['#0f2027', '#2c5364']),
        'linear-gradient(135deg, #0f2027, #2c5364)',
    );
    assert.equal(
        buildLinearGradient(90, ['#fff', '  ', '#000']),
        'linear-gradient(90deg, #fff, #000)',
    );
});

test('parseLinearGradient round-trips a built gradient', () => {
    const value = buildLinearGradient(200, ['#0f2027', '#203a43', '#2c5364']);
    assert.deepEqual(parseLinearGradient(value), {
        angle: 200,
        colors: ['#0f2027', '#203a43', '#2c5364'],
    });
});

test('parseLinearGradient defaults the angle and keeps rgb() stops intact', () => {
    assert.deepEqual(
        parseLinearGradient('linear-gradient(rgb(1, 2, 3), #fff)'),
        { angle: 135, colors: ['rgb(1, 2, 3)', '#fff'] },
    );
});

test('parseLinearGradient strips per-stop positions', () => {
    assert.deepEqual(
        parseLinearGradient('linear-gradient(45deg, #000 0%, #fff 100%)'),
        { angle: 45, colors: ['#000', '#fff'] },
    );
});

test('parseLinearGradient returns null for non-gradients', () => {
    assert.equal(parseLinearGradient('#0b1021'), null);
    assert.equal(parseLinearGradient('radial-gradient(#fff, #000)'), null);
    assert.equal(parseLinearGradient(null), null);
});
