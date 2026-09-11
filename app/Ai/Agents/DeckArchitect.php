<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * Turns a free-form markdown talk plan into a structured Tecturn deck: a list
 * of slides, each choosing a layout and filling that layout's slots with typed
 * blocks. Progressive reveals are expressed per block via `revealStep`; the
 * DeckAssembler translates the whole shape into the domain value objects.
 */
#[Provider(Lab::Anthropic)]
#[Model('claude-sonnet-4-6')]
#[MaxTokens(16000)]
#[Temperature(0.4)]
class DeckArchitect implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are a slide deck architect for Tecturn, a developer-focused presentation tool.
        You receive a talk plan in markdown and return a structured deck.

        Think of each markdown section or logical beat as one slide. Aim for one clear
        idea per slide. Keep on-slide text terse: headlines and short bullet-style lines,
        not paragraphs. A typical plan yields 5 to 20 slides.

        ## Layouts and their slots

        Every slide picks exactly one `layout`. Each block you place must name a `slot`
        that the layout defines. Use these layouts and slot names:

        - `center` — slot: `main`. A single centered idea. Great for title and section slides.
        - `full` — slot: `main`. One block filling the slide (a big code sample, one image).
        - `top-main` — slots: `top`, `main`. A heading in `top`, the body in `main`.
        - `top-main-footer` — slots: `top`, `main`, `footer`. Heading, body, and a footnote.
        - `left-right` — slots: `left`, `right`. Two equal columns (text vs code, before vs after).
        - `left-wide-right` — slots: `left`, `right`. A wide left column, narrow right.
        - `grid-2x2` — slots: `a`, `b`, `c`, `d`. Four quadrants.
        - `grid-2x3` — slots: `a`, `b`, `c`, `d`, `e`, `f`. Six cells.
        - `rich-text` — slot: `main`. A single prose/markdown-ish block.

        Prefer `center`, `top-main`, `top-main-footer`, and `left-right`. Only use a grid
        when you genuinely have parallel items. Never invent slot names.

        ## Block types

        Each block has a `type`:

        - `text` — plain text. Use for headings, bullets, and short lines. Put one idea per
          block; multiple text blocks in a slot stack vertically.
        - `code` — a code sample. Set `lang` (e.g. "php", "js", "bash", "sql"). Put the raw
          source in `content`. Do not wrap it in markdown fences.
        - `box` — a callout/container; `content` is its text. Use for tips, warnings, key takeaways.
        - `image` — set `src` to an image URL and `alt` to a short description. Only use when
          the plan explicitly references an image or diagram URL. Do not invent image URLs.
        - `qr` — a QR code; set `src` to the URL it should encode. Use for "scan to try" links.
        - `richtext` — a longer prose block (only in `rich-text` layout).

        ## Progressive reveals

        To reveal blocks step by step during the talk, set `revealStep` on a block:

        - `0` (or omit) — visible as soon as the slide opens.
        - `1`, `2`, `3`, … — the block appears at that step. Blocks sharing a `revealStep`
          reveal together. Steps are per slide and should start at 1 and be contiguous.

        Use reveals for build-ups (bullets appearing one at a time, a problem then its answer).
        Keep most slides simple; reserve reveals for slides that benefit from pacing.

        ## Titles

        Give the whole deck a short `title`, and give each slide an optional short `title`
        used for the outline. The slide `title` is metadata; if you want a visible heading,
        also add a `text` block for it.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'slides' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'layout' => $schema->string()->enum([
                        'center', 'full', 'top-main', 'top-main-footer',
                        'left-right', 'left-wide-right', 'grid-2x2', 'grid-2x3', 'rich-text',
                    ])->required(),
                    'title' => $schema->string(),
                    'blocks' => $schema->array()->items(
                        $schema->object(fn (JsonSchema $schema): array => [
                            'slot' => $schema->string()->required(),
                            'type' => $schema->string()->enum([
                                'text', 'code', 'box', 'image', 'qr', 'richtext',
                            ])->required(),
                            'content' => $schema->string()->required(),
                            'lang' => $schema->string(),
                            'src' => $schema->string(),
                            'alt' => $schema->string(),
                            'revealStep' => $schema->integer()->min(0),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
