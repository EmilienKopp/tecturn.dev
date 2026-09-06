/**
 * Tests for the content linter (resources/js/lib/tecturn/CodeGeneration/lint.ts).
 *
 * Run with: npm run test:js
 *
 * Node strips the TypeScript on import natively, the same way present.mjs runs
 * the codegen, so no bundler or extra dependency is involved.
 */
import assert from 'node:assert/strict';
import test from 'node:test';
import {
    blockProseText,
    countUnits,
    lintDeck,
    lintSlide,
    speakingSeconds,
    stripInlineHtml,
} from '../resources/js/lib/tecturn/CodeGeneration/lint.ts';

// Mirrors config/lint.php defaults. The backend owns the real values; this is
// just a fixture so the counting/judging logic can be exercised in isolation.
const POLICY = {
    wordsPerMinute: 130,
    cjkCharsPerMinute: 300,
    wordsGood: 40,
    wordsMax: 75,
    cjkCharsGood: 90,
    cjkCharsMax: 170,
    paceUnderRatio: 0.85,
    paceOverRatio: 1.0,
};

const block = (type, content, extra = {}) => ({
    id: `block-${Math.random()}`,
    type,
    content,
    style: {},
    transition: null,
    lang: null,
    src: null,
    alt: null,
    actions: [],
    ...extra,
});

const slide = (blocks) => ({
    id: `slide-${Math.random()}`,
    layout: 'free',
    background: null,
    slots: { main: blocks },
    config: null,
    title: null,
});

const words = (text) => Array.from({ length: text }, () => 'word').join(' ');

test('stripInlineHtml drops tags, keeps text, decodes entities', () => {
    assert.equal(stripInlineHtml('a<br>b'), 'a b');
    assert.equal(
        stripInlineHtml('<span style="color: red">Hello</span> world'),
        'Hello world',
    );
    assert.equal(stripInlineHtml('Tom &amp; Jerry &lt;3'), 'Tom & Jerry <3');
});

test('countUnits counts Latin words', () => {
    const counts = countUnits('the quick brown fox jumps');

    assert.equal(counts.words, 5);
    assert.equal(counts.cjkChars, 0);
});

test('countUnits counts CJK characters, not spaces', () => {
    // Japanese has no word delimiters — each character is its own unit.
    const counts = countUnits('これはテストです');

    assert.equal(counts.cjkChars, 8);
    assert.equal(counts.words, 0);
});

test('countUnits handles a mixed-script line', () => {
    const counts = countUnits('React は素晴らしい library');

    assert.equal(counts.words, 2); // React, library
    assert.equal(counts.cjkChars, 6); // は素晴らしい
});

test('blockProseText excludes code blocks', () => {
    assert.equal(blockProseText(block('code', 'const x = 1;')), '');
    assert.equal(blockProseText(block('image', '')), '');
    assert.equal(blockProseText(block('text', 'hello world')), 'hello world');
});

test('blockProseText extracts richtext prose and skips embedded code', () => {
    const doc = JSON.stringify({
        blocks: [
            { type: 'header', data: { text: 'Title <b>here</b>' } },
            { type: 'paragraph', data: { text: 'Some body text' } },
            { type: 'list', data: { items: ['one', 'two'] } },
            { type: 'code', data: { code: 'ignored();' } },
        ],
    });

    assert.equal(
        blockProseText(block('richtext', doc)),
        'Title here Some body text one two',
    );
});

test('lintSlide: a lean English slide reads good', () => {
    const linted = lintSlide(slide([block('text', words(20))]), POLICY);

    assert.equal(linted.words, 20);
    assert.equal(linted.verdict, 'good');
    assert.equal(linted.dominantScript, 'words');
});

test('lintSlide: a moderately full slide warns', () => {
    const linted = lintSlide(slide([block('text', words(60))]), POLICY);

    assert.equal(linted.verdict, 'warn');
});

test('lintSlide: a wall of text is over', () => {
    const linted = lintSlide(slide([block('text', words(120))]), POLICY);

    assert.equal(linted.verdict, 'over');
});

test('lintSlide: CJK slide judged on characters', () => {
    const dense = 'あ'.repeat(200);
    const linted = lintSlide(slide([block('text', dense)]), POLICY);

    assert.equal(linted.cjkChars, 200);
    assert.equal(linted.dominantScript, 'cjk');
    assert.equal(linted.verdict, 'over');
});

test('speakingSeconds blends both scripts', () => {
    assert.equal(
        speakingSeconds({ words: 130, cjkChars: 0, chars: 0 }, POLICY),
        60,
    );
    assert.equal(
        speakingSeconds({ words: 0, cjkChars: 300, chars: 0 }, POLICY),
        60,
    );
    assert.equal(
        speakingSeconds({ words: 65, cjkChars: 150, chars: 0 }, POLICY),
        60,
    );
});

test('lintDeck totals time and reports pace against the target', () => {
    const slides = [
        slide([block('text', words(130))]), // ~60s
        slide([block('text', words(130))]), // ~60s
    ];

    const noTarget = lintDeck(slides, POLICY, null);
    assert.equal(noTarget.totalSpeakingSeconds, 120);
    assert.equal(noTarget.pace, null);

    assert.equal(lintDeck(slides, POLICY, 2).pace, 'on'); // 120s vs 120s target
    assert.equal(lintDeck(slides, POLICY, 10).pace, 'under'); // way short of 600s
    assert.equal(lintDeck(slides, POLICY, 1).pace, 'over'); // 120s vs 60s target
});
