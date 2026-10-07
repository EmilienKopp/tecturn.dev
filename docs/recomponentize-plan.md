# Recomponentize: shadcn → daisy-svelte

Branch: `recomponentize`. Goal: replace shadcn-svelte components with `../daisy-svelte`
(own Svelte 5 + daisyUI 5 library), keep Stage Light v2 theme, migrate incrementally
(both libraries coexist during migration).

## Audit summary (2026-10-07)

- 22 shadcn components in `resources/js/components/ui/`, 137 imports total.
- Heavy: button (41 files), dialog (21), label (16), input (15).
- Medium: sidebar (8), dropdown-menu (7), tooltip (5), avatar (4), badge/checkbox/sonner (3).
- One-offs: sheet, breadcrumb, skeleton, switch, navigation-menu, alert, select, separator.
- Dead (zero imports, delete): card, collapsible, spinner.
- bits-ui only actually used by: select, switch, tooltip. dialog/dropdown/checkbox are custom.
- Stage Light tokens (`bg-muted`, `text-muted-foreground`, …) appear ~640× in 53 files
  outside ui/ — these are OUR tailwind tokens, NOT shadcn. Do NOT sweep; keep both token
  vocabularies coexisting. New code uses daisy classes.

## Component mapping

| lecturn (shadcn) | daisy-svelte / daisyUI |
|---|---|
| button | Button / BaseButton |
| dialog | Modal (native `<dialog>`, bind:open + onclose) |
| input / label | Input / InputLabel (+ InputError) |
| dropdown-menu | Dropdown (native `<details>/<summary>`) |
| checkbox / switch | Checkbox / Toggle |
| select | Select |
| badge / alert / breadcrumb | Badge / Alert / Breadcrumbs |
| sonner | Toaster (+ toast store) — drops svelte-sonner |
| sheet | Drawer (1 use) |
| tooltip | pure CSS: `class="tooltip" data-tip="…"` (no component) |
| avatar / skeleton / separator / navigation-menu | daisyUI classes: avatar / skeleton / divider / menu·navbar |
| sidebar | KEEP as-is (self-contained, 13 subcomponents); restyle/extract later |

## Phases

1. **Setup** ← current
   - `npm i -D daisyui`, add `daisy-svelte` via `file:../daisy-svelte`
   - `@plugin "daisyui"` + custom Stage Light theme via `@plugin "daisyui/theme"` in app.css
     (map Stage Light values → `--color-primary`, `--color-base-100`, `--color-base-content`, …)
   - `@source "../../node_modules/daisy-svelte/dist"` so Tailwind 4 scans the package
   - daisy-svelte must be built/packaged (`npm run package` → dist/)
2. **Leaf swaps** — button, badge, input, label, checkbox, switch, alert, breadcrumb,
   skeleton; delete card/collapsible/spinner (dead)
3. **Behavior swaps** — dialog ×21 (biggest chunk), dropdown ×7, select ×2, sonner→Toaster ×3
4. **CSS-only** — tooltip ×5, avatar ×4, separator ×2
5. **Sidebar** — keep; restyle with daisy tokens whenever
6. **Cleanup** — remove bits-ui, svelte-sonner; keep clsx/tailwind-merge/lucide;
   delete emptied `components/ui/*` dirs; remove `components.json` (shadcn config)

## Gotchas

- daisyUI is a devDep of daisy-svelte → lecturn must install it itself.
- Tailwind 4 ignores node_modules by default → the `@source` line is mandatory.
- Upstream nicety before/during phase 4: add Tooltip + Avatar wrappers to daisy-svelte.
