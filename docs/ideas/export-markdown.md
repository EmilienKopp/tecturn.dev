# Export to Markdown

Status: design / not built. Companion to [dsl.md](./dsl.md) — that doc covers
Markdown **in** (markdown → blocks); this covers Markdown **out** (deck →
markdown document). Export is a content capture for notes, LLMs, printing, or
hand-off, not a runnable build.

## Where it fits

Mirror the existing **client-side** "Export Svelte" path, not the server-backed
web-component/JSON path (see [presenter-export.md](../presenter-export.md)):

- Generation lives in `CodeGeneration/` (DOM-free TS, the single source of
  truth), exported through the `codegen.ts` shim the editor imports from.
- `Editor.svelte` snapshots `editor.content`/`editor.flow` and hands the string
  to `downloadFile(`${slugify(name)}.md`, …, 'text/markdown')`.
- No backend, no Node subprocess, no `/export` route.

## Interplay with the Markdown DSL (feat/markdown)

Once `config.markdown` slides land (dsl.md, layers 1–4), export should be a
**near round-trip**: for a markdown-backed slide, emit `slide.config.markdown`
verbatim (optionally stripping `@@N` reveal markers and lowering `<primary>`-style
branding tags to plain text). For a block-based slide, serialize the blocks as
below. Until then, every slide is block-based.

## Serialization

`generatePresentationMarkdown(content, flow = null, options = { enabledOnly:false })`
in a new `CodeGeneration/PresentationToMarkdown.ts` (a standalone pure function —
the codegen container/RenderContext carries steps/refs/transitions that a flat
markdown dump does not need).

- **Slides:** iterate `content.slides` in array order (it mirrors the nav chain).
  Include **all** slides by default — a content export, like JSON, not the
  playable subset. `enabledOnly` (via `enabledSlideIds`) left as a future opt-in.
- **Heading:** non-blank `slide.title` → `# {title}`; null/blank → no heading.
- **Slots/blocks:** the layout's canonical slot order (`layouts.ts`, currently
  `['main']` for `full`/`center`/`free`), then any extra slot keys so export
  stays lossless; block array order within a slot.
- **Separators:** drop empty slides; join with `\n\n---\n\n`.

Block → Markdown:

| type | output |
|------|--------|
| `text` | paragraph via `htmlToMarkdown(content)` |
| `box` | blockquote (`> ` per line) via `htmlToMarkdown` |
| `code` | fenced ```` ```{lang} ````; grow the fence if `content` holds a backtick run |
| `qr` | `[{src}]({src})` when `src` set, else omit |
| `image` | `![{alt}]({src})` |
| `richtext` | walk EditorJs JSON: header→`#`×level, list→`-`/`1.`, code→fence, quote→`> `, paragraph→`htmlToMarkdown`; parse failure → `stripInlineFormatting` |

### Inline HTML → Markdown

Text/box content is sanitized inline HTML (`sanitize.ts` allowlist: styled
`span`, `b/strong`, `i/em`, `br`). Add a sibling `htmlToMarkdown(html)` reusing
`stripInlineFormatting`'s DOM-free char-scan:

- `b`/`strong`, `span[font-weight: bold|≥600]` → `**…**`
- `i`/`em`, `span[font-style: italic]` → `*…*`
- `<br>` → newline; color/size-only spans → drop tag, keep text
- decode `&lt; &gt; &amp; &quot; &#39;`; escape a minimal set (`` \ ` * _ [ ] ``)

## Wiring

- Export `generatePresentationMarkdown` from `CodeGeneration/index.ts` and the
  `codegen.ts` shim.
- `Editor.svelte`: `exportMarkdown()` handler + `onExportMarkdown` prop.
- `EditorToolbar.svelte`: `onExportMarkdown` prop, a `FileText` menu item after
  JSON, `data-test="editor-export-markdown-button"`.

## Tests

`scripts/markdown-export.test.mjs` (node:test, `npm run test:js`; pattern per
`scripts/flow-order.test.mjs`): every `htmlToMarkdown` mapping (bold/italic via
tag and via span-style, `<br>`, entity decode, dropped color span, nested
bold+italic, markdown-char escaping); each block type; code fence with/without
lang and with an embedded backtick run; qr link + empty-src omission; image
alt/no-alt; richtext header/list/quote/code/paragraph + parse-failure fallback;
multi-slide separators; empty slide dropped; no-title slide; all vs `enabledOnly`.

## Biggest risk

`htmlToMarkdown` — a hand-rolled parser juggling tag→marker nesting, entity
decode, and markdown escaping. Keep the escape set minimal and deterministic,
reuse the proven `stripInlineFormatting` scan, and test nesting/entities/escapes
explicitly.
