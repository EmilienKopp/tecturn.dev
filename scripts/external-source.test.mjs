/**
 * Tests for the external source helper
 * (resources/js/lib/tecturn/external-source.ts).
 *
 * Run with: npm run test:js
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import { googleSlidesEmbedUrl } from '../resources/js/lib/tecturn/external-source.ts';

test('googleSlidesEmbedUrl returns null for empty input', () => {
    assert.equal(googleSlidesEmbedUrl(null), null);
    assert.equal(googleSlidesEmbedUrl(undefined), null);
    assert.equal(googleSlidesEmbedUrl(''), null);
});

test('googleSlidesEmbedUrl rewrites an edit link to the embed form', () => {
    assert.equal(
        googleSlidesEmbedUrl(
            'https://docs.google.com/presentation/d/abc123/edit#slide=id.p',
        ),
        'https://docs.google.com/presentation/d/abc123/embed?start=false&loop=false&delayms=5000',
    );
});

test('googleSlidesEmbedUrl rewrites a published link to the embed form', () => {
    assert.equal(
        googleSlidesEmbedUrl(
            'https://docs.google.com/presentation/d/e/2PACX-xyz/pub?start=false&loop=false',
        ),
        'https://docs.google.com/presentation/d/e/2PACX-xyz/embed?start=false&loop=false&delayms=5000',
    );
});

test('googleSlidesEmbedUrl leaves an already-embed link untouched', () => {
    const embed =
        'https://docs.google.com/presentation/d/abc123/embed?start=false';
    assert.equal(googleSlidesEmbedUrl(embed), embed);
});
