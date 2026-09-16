/**
 * Tests for the inline-HTML sanitizer
 * (resources/js/lib/tecturn/CodeGeneration/sanitize.ts).
 *
 * Run with: npm run test:js
 *
 * Node strips the TypeScript on import natively, so no bundler is involved.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    sanitizeInlineHtml,
    stripInlineFormatting,
} from '../resources/js/lib/tecturn/CodeGeneration/sanitize.ts';

test('sanitizeInlineHtml keeps allowlisted markup', () => {
    assert.equal(
        sanitizeInlineHtml('<b>Hi</b> <span style="color: #fff">there</span>'),
        '<b>Hi</b> <span style="color: #fff">there</span>',
    );
});

test('sanitizeInlineHtml normalizes an inline px font-size to cqw', () => {
    // 24.576px baked in by copy-paste -> stage-relative cqw so it scales.
    assert.equal(
        sanitizeInlineHtml('<span style="font-size: 24.576px">x</span>'),
        '<span style="font-size: 2.458cqw">x</span>',
    );
});

test('sanitizeInlineHtml leaves an inline cqw font-size untouched', () => {
    assert.equal(
        sanitizeInlineHtml('<span style="font-size: 3.2cqw">x</span>'),
        '<span style="font-size: 3.2cqw">x</span>',
    );
});

test('stripInlineFormatting removes spans and bold but keeps text', () => {
    assert.equal(
        stripInlineFormatting(
            '<span style="color: #fff; font-size: 2em">Big</span> <b>bold</b> text',
        ),
        'Big bold text',
    );
});

test('stripInlineFormatting preserves line breaks', () => {
    assert.equal(
        stripInlineFormatting('one<br>two<br/>three'),
        'one<br>two<br>three',
    );
});

test('stripInlineFormatting drops disallowed tags but keeps their text', () => {
    assert.equal(
        stripInlineFormatting('<div class="x"><script>bad</script>ok</div>'),
        'badok',
    );
});

test('stripInlineFormatting escapes a stray closing bracket', () => {
    assert.equal(stripInlineFormatting('5 > 3'), '5 &gt; 3');
});

test('stripInlineFormatting escapes an unclosed opening bracket', () => {
    assert.equal(stripInlineFormatting('a < b'), 'a &lt; b');
});

test('stripInlineFormatting on empty input yields empty string', () => {
    assert.equal(stripInlineFormatting(''), '');
});

test('stripInlineFormatting is idempotent on already-plain text', () => {
    const plain = stripInlineFormatting('<b>hello</b><br>world');

    assert.equal(stripInlineFormatting(plain), plain);
});
