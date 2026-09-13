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
#[Provider(Lab::Mistral)]
#[Model('mistral-medium-3-5')]
#[MaxTokens(16000)]
#[Temperature(0.4)]
class DeckArchitect implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        You are a slide deck architect for Tecturn, a developer-focused presentation tool.
        You receive a talk plan in markdown or raw text and return a structured deck.

        Think of each markdown section or logical beat as one slide. Aim for one clear
        idea per slide. Keep on-slide text terse: headlines and short bullet-style lines,
        not paragraphs. A typical plan yields 5 to 20 slides.

        ## Layouts and their slots

        Every slide picks exactly one `layout`. Each block you place must name a `slot`
        that the layout defines. Only these three layouts are available:

        - `center` — slot: `main`. Blocks stack centered on the slide. Your default: use it
          for title slides, section breaks, and most content slides.
        - `full` — slot: `main`. Blocks fill the slide top-aligned. Good for one big code
          sample, a single image, or a dense list.
        - `free` — slot: `main`. Blocks are placed freely; only use it when you specifically
          want loose positioning. Prefer `center` and `full` otherwise.

        Every block's `slot` must be `main`. Never invent other slot names or layouts.

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
        - `richtext` — a longer prose block. Use sparingly for a paragraph of running text.

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

        ## Styling — always style the deck

        A deck must never render as unstyled black-on-white text. Pick a deliberate look
        and apply it consistently.

        Set a deck-level `theme`:

        - `background` — the slide background. A solid CSS color or a CSS gradient string,
          e.g. `#0b1021`, `#faf7f2`, or `linear-gradient(135deg, #0f2027, #203a43)`.
        - `textColor` — the default text color for every slide. It MUST contrast strongly
          with `background` (light text on a dark background, dark text on a light one).
        - `accentColor` — a highlight color for key words, numbers, and emphasis blocks.
          It must be readable on `background`.
        - `headingFont` and `bodyFont` — choose from: `Instrument Sans`, `Inter`,
          `Bricolage Grotesque`, `Anton`, `Lora`, `JetBrains Mono`. Anton suits big punchy
          headings; Lora is a serif for prose; Inter and Instrument Sans are clean bodies.

        Then style individual blocks with an optional `style` object to create hierarchy:

        - `fontSize` — an absolute size in `rem`. Size by relevance, not uniformly:
          - Hero / title lines: `3.5rem`–`5rem`.
          - Slide headings: `2.5rem`–`3rem`.
          - Body and bullets: `1.5rem`–`2rem`.
          - Footnotes, captions, asides: `1rem`–`1.25rem`.
        - `fontWeight` — e.g. `400`, `600`, `700`, `800`. Make headings heavier than body.
        - `color` — override the slide default for a single block. Use `accentColor` here to
          make a key phrase or number pop. Only set this when you want a block to differ
          from the slide default.
        - `fontFamily` — override the font for one block (e.g. a `headingFont` on a title,
          or `JetBrains Mono` on an inline command).

        Guidelines: keep the palette to 2–3 colors. Give the most important block on each
        slide the largest size and heaviest weight. Never make text the same color as the
        background. You do not need a `style` on every block; unstyled text inherits the
        theme `textColor` and a sensible default size.
        PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        $fonts = ['Instrument Sans', 'Inter', 'Bricolage Grotesque', 'Anton', 'Lora', 'JetBrains Mono'];

        return [
            'title' => $schema->string()->required(),
            'theme' => $schema->object(fn (JsonSchema $schema): array => [
                'background' => $schema->string()->required(),
                'textColor' => $schema->string()->required(),
                'accentColor' => $schema->string(),
                'headingFont' => $schema->string()->enum($fonts),
                'bodyFont' => $schema->string()->enum($fonts),
            ])->required(),
            'slides' => $schema->array()->items(
                $schema->object(fn (JsonSchema $schema): array => [
                    'layout' => $schema->string()->enum([
                        'center', 'full', 'free',
                    ])->required(),
                    'title' => $schema->string(),
                    'background' => $schema->string(),
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
                            'style' => $schema->object(fn (JsonSchema $schema): array => [
                                'fontSize' => $schema->string(),
                                'fontWeight' => $schema->string(),
                                'color' => $schema->string(),
                                'fontFamily' => $schema->string()->enum($fonts),
                            ]),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }
}
