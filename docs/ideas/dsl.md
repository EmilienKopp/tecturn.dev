# DSL — the Deck Structuring Language

Status: napkin sketch / experiment. Not a committed feature.

A stupid idea with a good name: a markdown-based authoring language for decks,
called DSL. Yes, "just write it in DSL" is a tautology. That is the point.

"Deck Structuring Language" over "Deck Skeleton Language" — skeleton implies
outline-only, but this is meant to carry layout, flow, and styling too.

## The core idea

Mostly markdown for content, plus a small set of extra constructs for
structure and behavior. Three extra layers on top of plain markdown:

- `@@N` — flow / reveal directives (already built)
- `<inline>` tags — text styling on a span (already built)
- `:::` fences — layout regions / blocks (the new, unbuilt part)

### Syntax division of labor

Keep the two tag styles from fighting each other by giving each a job:

- `:::` **owns vertical space.** Block-level, on its own line. Regions,
  columns, asides, callouts. Can nest, fails loud when unbalanced.
- `<tag>` **owns inline runs.** Lives inside a line. Accent a word, light up a
  keyword. Already solved via the sanitized-span path.

Colon = layout, angle = text styling. A reader can tell at a glance which is
which, and the parser never has to guess whether `<center>` wraps a block or a
word. This also sidesteps the "looks like HTML but isn't HTML" trap that bare
`<center>` blocks would invite (nesting rules, attributes, strict closing).

Rough shape:

```
::: columns
## Left
@@1 first reveal

:::
## Right
Something with an <accent>important</accent> word
:::
```

Flow directives still work _inside_ a region. That composition (`:::` + `@@N`
nesting cleanly) is what turns this from "markdown plus decoration" into an
actual little language.

### The one hard part

`:::` fences force real grammar work that `<>` lets you be lazy about:
nesting and close-matching. Columns inside a section means tracking fence
depth, and unbalanced fences must fail loudly. That is a feature for an
authoring tool, not a cost.

## What already exists (the pipeline this plugs into)

The DSL is not greenfield. Layers 1, 3, and 4 below already ship or are in
flight on `feat/markdown`. The only genuinely new work is the `:::` block layer.

```
Markdown (slide.config.markdown)
    ↓  parseMarkdownSlide + compileInline + sanitizeInlineHtml
Canonical blocks (slide.slots.main: Block[])
    ↓  syncMarkdownFlow
Flow graph (transition nodes + edges: node-md-*, edge-md-*)
    ↓  render plugins via CodegenContainer
Svelte component source (with <Transition> reveal order)
```

### 1. Markdown source storage

- `config.markdown` is the source of truth. Stored opaquely as one key inside
  `slide.config`; derived blocks live in `slide.slots.main`.
- DB: `PresentationModel.content` (JSON) → `slides[].config.markdown`.
- Value objects: `app/Domain/Presentation/ValueObjects/PresentationContent.php`,
  `Block.php`.

### 2. `@@N` flow-node parsing (on `feat/markdown`)

- `resources/js/lib/tecturn/CodeGeneration/markdown.ts`
- `parseMarkdownSlide(markdown, slideId, branding) → ParsedMarkdownSlide`
  scans with `/ @@(\d+)$/` on trailing lines of headings, paragraphs, list
  items, quotes, images, code fences. Each marked construct becomes a `Block`
  with `transition: { nodeId: "node-md-{slideId}-{N}" }`.
- `syncMarkdownFlow(flow, slideId, steps)` creates deterministic transition
  nodes and chains them `slide → N1 → N2 → …`, canvas-position independent.
- Deterministic IDs via fnv1a hashing of `slideId + construct.raw + occurrence`.

### 3. Inline formatting sanitizer (live on main)

- `resources/js/lib/tecturn/CodeGeneration/sanitize.ts`
- `sanitizeInlineHtml(html)` — DOM-free, allowlist only: `span` (validated
  style), `b`, `strong`, `i`, `em`, `br`. `stripInlineFormatting(html)` is the
  clear-formatting escape hatch.
- Style validators for `color`, `font-size` (incl. `cqw`/`cqh`), `font-weight`,
  `font-style`. Sizes normalized to stage-relative `cqw` via `scaleFontSize()`.

### 4. Branding baked at parse time

- `branding.ts` (`BRANDING_KEYS`, `currentBranding()`) maps keys like
  `primary` / `accent` / `success` to hex.
- `compileInline(lines, branding)` in `markdown.ts` rewrites
  `<primary>text</primary>` → `<span style="color:#…">text</span>` at parse
  time, validates tag names against `/^[a-z][a-z0-9-]*$/i`, then sanitizes.
- Branding is frozen into the content snapshot. No runtime color lookup.

### 5. Codegen plugin architecture (live on main)

- Generic IoC: `resources/js/lib/container.ts` (`Container`, `Registry`).
- `CodeGeneration/Container.ts` — `CodegenContainer` with `blockRenderers` and
  `layoutRenderers` registries; `use(plugin)` registers by type/layout name.
- Plugin contracts (`contracts.ts`): `BlockRendererPlugin.render(block,depth,rc)`,
  `LayoutRendererPlugin.render(slide,rc)`, bundled as `CodegenPlugin`.
- Built-ins in `plugins/`: `blocks.ts` (Richtext, Code, Image, Box, Qr,
  Paragraph fallback), `layouts.ts` (Slot grid + transitions, Free absolute).

## How the `:::` layer would plug in

1. **Parser** — in `scan()` (markdown.ts), detect a `::: \w+` line, capture the
   type name, collect body until the matching `:::`, push a new `Construct`
   (`type: 'container'` or domain-specific: `callout`, `columns`, `aside`).
   Track fence depth for nesting; error on imbalance. `@@N` on a fence works the
   same as on any construct.
2. **Renderer** — add a `BlockRendererPlugin` (for in-slot boxes) or a
   `LayoutRendererPlugin` (for full-slide regions like columns), register via
   `CodegenContainer.use()`.
3. Everything downstream (flow sync, codegen, `<Transition>` ordering) is reused
   as-is.

## Open questions

- Do fences take attributes? `::: columns cols=2` vs. positional-only.
- What is the region vocabulary? (`columns`, `center`, `aside`, `callout`, …)
- How do nested fences map onto slots vs. free layout?
- Is `.dsl` a real file extension we want, or does content stay markdown-in-JSON?

```

```
