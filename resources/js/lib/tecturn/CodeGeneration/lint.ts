/**
 * Content linter — an opinionated, language-aware read of how much prose a
 * slide (and the whole deck) carries, plus a speaking-time estimate.
 *
 * It lives here, next to the codegen, because it must stay DOM-free and hold
 * the counting *method* in one place: the editor shows it live while you build,
 * and the same functions can later feed the export/analytics report from the
 * Node subprocess. No `document`, no framework — pure string work.
 *
 * The threshold *values* (speaking rates, density ceilings, pace tolerance) are
 * NOT defined here. They are owned by the backend (config/lint.php → LintPolicy)
 * and passed in as a `LintPolicy`, so the numbers never drift between PHP and
 * TS. This module only decides how to count and judge.
 *
 * Two counting modes on purpose. Space-delimited scripts (Latin, Cyrillic,
 * Greek…) are judged by word count; scripts without word delimiters (Chinese,
 * Japanese, Korean) are judged by character count. A slide can mix both, so we
 * always track each independently and combine them proportionally.
 */
import type { Block, LintPolicy, Slide } from '@/types/generated';
import type { EditorJsBlock, EditorJsOutput } from './support.ts';

export type { LintPolicy };

/** Block types whose content is prose the audience reads and you narrate. */
const PROSE_BLOCK_TYPES = new Set(['text', 'box', 'richtext']);

/**
 * Characters from scripts that don't separate words with spaces. Each one is
 * counted as its own unit. Covers Hiragana, Katakana (incl. half-width), CJK
 * ideographs (+ Extension A and compatibility), and Hangul.
 */
const CJK_CHAR = /[぀-ヿ㐀-䶿一-鿿豈-﫿ｦ-ﾟ가-힯]/g;

export type ContentVerdict = 'good' | 'warn' | 'over';
export type DeckPace = 'under' | 'on' | 'over';

export type UnitCounts = {
    /** Word tokens from space-delimited scripts. */
    words: number;
    /** Individual characters from CJK scripts. */
    cjkChars: number;
    /** Total visible characters (both scripts), whitespace excluded. */
    chars: number;
};

export type SlideLint = UnitCounts & {
    /** Estimated time to narrate this slide's prose, in whole seconds. */
    speakingSeconds: number;
    /** Density judgement for the slide. */
    verdict: ContentVerdict;
    /** Which script drives the judgement, for picking what number to show. */
    dominantScript: 'words' | 'cjk';
};

export type DeckLint = {
    slides: SlideLint[];
    /** Sum of every slide's speaking-time estimate, in seconds. */
    totalSpeakingSeconds: number;
    /** Target duration in seconds, or null when none is set. */
    targetSeconds: number | null;
    /** How the estimate compares to the target, or null when none is set. */
    pace: DeckPace | null;
};

const NAMED_ENTITIES: Record<string, string> = {
    amp: '&',
    lt: '<',
    gt: '>',
    quot: '"',
    apos: "'",
    nbsp: ' ',
};

/** Decode the handful of entities our stored inline HTML can contain. */
function decodeEntities(text: string): string {
    return text.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (whole, body: string) => {
        if (body[0] === '#') {
            const code =
                body[1] === 'x' || body[1] === 'X'
                    ? parseInt(body.slice(2), 16)
                    : parseInt(body.slice(1), 10);

            return Number.isFinite(code) ? String.fromCodePoint(code) : whole;
        }

        return NAMED_ENTITIES[body.toLowerCase()] ?? whole;
    });
}

/**
 * Reduce inline-formatted HTML (or any tag soup) to its readable text: tags
 * become spaces so `a<br>b` stays two words, entities decode, runs of
 * whitespace collapse.
 */
export function stripInlineHtml(html: string): string {
    if (!html) {
        return '';
    }

    return decodeEntities(html.replace(/<[^>]*>/g, ' '))
        .replace(/\s+/g, ' ')
        .trim();
}

/** Pull the narratable text out of an EditorJS (richtext) document. */
function richtextToText(content: string): string {
    let output: EditorJsOutput;

    try {
        output = JSON.parse(content) as EditorJsOutput;
    } catch {
        return stripInlineHtml(content);
    }

    const pieces: string[] = [];

    for (const ejsBlock of output.blocks ?? []) {
        pieces.push(editorJsBlockToText(ejsBlock));
    }

    return pieces.join(' ').replace(/\s+/g, ' ').trim();
}

function editorJsBlockToText(ejsBlock: EditorJsBlock): string {
    const { type, data } = ejsBlock;

    // Code inside a richtext block is code, not prose — skip it, mirroring how
    // standalone code blocks are excluded from the reading load.
    if (type === 'code') {
        return '';
    }

    if (type === 'list' && Array.isArray(data.items)) {
        return (data.items as string[])
            .map((item) => stripInlineHtml(String(item)))
            .join(' ');
    }

    return stripInlineHtml(String(data.text ?? ''));
}

/** The prose text of a single block, or '' for non-prose (code, image). */
export function blockProseText(block: Block): string {
    if (!PROSE_BLOCK_TYPES.has(block.type)) {
        return '';
    }

    if (block.type === 'richtext') {
        return richtextToText(block.content);
    }

    return stripInlineHtml(block.content);
}

/** Every prose block across a slide's slots, joined. */
export function slideProseText(slide: Slide): string {
    const pieces: string[] = [];

    for (const blocks of Object.values(slide.slots ?? {})) {
        for (const block of blocks) {
            const text = blockProseText(block);

            if (text) {
                pieces.push(text);
            }
        }
    }

    return pieces.join(' ');
}

/** Count word tokens and CJK characters in a run of plain text. */
export function countUnits(text: string): UnitCounts {
    const cjkChars = (text.match(CJK_CHAR) ?? []).length;

    const words = text
        .replace(CJK_CHAR, ' ')
        .split(/\s+/)
        .filter((token) => /[\p{L}\p{N}]/u.test(token)).length;

    const chars = text.replace(/\s+/g, '').length;

    return { words, cjkChars, chars };
}

/** Speaking-time estimate for a set of counts, in whole seconds. */
export function speakingSeconds(
    counts: UnitCounts,
    policy: LintPolicy,
): number {
    const fromWords = (counts.words / policy.wordsPerMinute) * 60;
    const fromCjk = (counts.cjkChars / policy.cjkCharsPerMinute) * 60;

    return Math.round(fromWords + fromCjk);
}

/** The density verdict for a set of counts, combining both scripts. */
function densityVerdict(
    counts: UnitCounts,
    policy: LintPolicy,
): ContentVerdict {
    if (counts.words === 0 && counts.cjkChars === 0) {
        return 'good';
    }

    const goodLoad =
        counts.words / policy.wordsGood + counts.cjkChars / policy.cjkCharsGood;
    const maxLoad =
        counts.words / policy.wordsMax + counts.cjkChars / policy.cjkCharsMax;

    if (goodLoad <= 1) {
        return 'good';
    }

    return maxLoad <= 1 ? 'warn' : 'over';
}

/** Lint a single slide's prose against the backend-owned policy. */
export function lintSlide(slide: Slide, policy: LintPolicy): SlideLint {
    const counts = countUnits(slideProseText(slide));

    return {
        ...counts,
        speakingSeconds: speakingSeconds(counts, policy),
        verdict: densityVerdict(counts, policy),
        dominantScript: counts.cjkChars > counts.words ? 'cjk' : 'words',
    };
}

/**
 * Lint a whole deck. `targetMinutes` is the presenter's ideal talk length
 * (TalkSettings.durationMinutes); pass null/undefined to skip the pace check.
 */
export function lintDeck(
    slides: Slide[],
    policy: LintPolicy,
    targetMinutes: number | null = null,
): DeckLint {
    const lintedSlides = slides.map((slide) => lintSlide(slide, policy));
    const totalSpeakingSeconds = lintedSlides.reduce(
        (sum, slide) => sum + slide.speakingSeconds,
        0,
    );

    const targetSeconds =
        targetMinutes && targetMinutes > 0 ? targetMinutes * 60 : null;

    let pace: DeckPace | null = null;

    if (targetSeconds !== null) {
        if (totalSpeakingSeconds < targetSeconds * policy.paceUnderRatio) {
            pace = 'under';
        } else if (
            totalSpeakingSeconds >
            targetSeconds * policy.paceOverRatio
        ) {
            pace = 'over';
        } else {
            pace = 'on';
        }
    }

    return { slides: lintedSlides, totalSpeakingSeconds, targetSeconds, pace };
}

/** Format a seconds count as `M:SS` (or `H:MM:SS` past an hour). */
export function formatSpeakingTime(seconds: number): string {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;

    if (h > 0) {
        return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    return `${m}:${String(s).padStart(2, '0')}`;
}
