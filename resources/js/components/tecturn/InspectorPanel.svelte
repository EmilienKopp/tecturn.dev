<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Eye from 'lucide-svelte/icons/eye';
    import EyeOff from 'lucide-svelte/icons/eye-off';
    import ImageOff from 'lucide-svelte/icons/image-off';
    import RotateCcw from 'lucide-svelte/icons/rotate-ccw';
    import SquarePen from 'lucide-svelte/icons/square-pen';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import PresentationBackgroundController from '@/actions/App/Http/Controllers/Presentations/PresentationBackgroundController';
    import UploadPresentationImageController from '@/actions/App/Http/Controllers/Presentations/UploadPresentationImageController';
    import ColorField from '@/components/tecturn/ColorField.svelte';
    import GradientModal from '@/components/tecturn/GradientModal.svelte';
    import ImageLibraryModal from '@/components/tecturn/ImageLibraryModal.svelte';
    import LayoutPicker from '@/components/tecturn/LayoutPicker.svelte';
    import { Button, Modal, toast } from 'daisy-svelte';
    import { isGradientBackground } from '@/lib/tecturn/background';
    import {
        formatSpeakingTime,
        lintDeck,
        lintSlide,
    } from '@/lib/tecturn/CodeGeneration/lint';
    import type { EditorState } from '@/lib/tecturn/editor-state.svelte';
    import { FONTS } from '@/lib/tecturn/fonts';
    import { SUPPORTED_LANGUAGES } from '@/lib/tecturn/shiki';
    import { uploadImage, xsrfToken } from '@/lib/tecturn/uploads';
    import type { LintPolicy } from '@/types/generated';

    let {
        editor,
        presentationId,
        policy,
        targetMinutes = null,
        presentationStyle = 'balanced',
        onEditCodeSequence,
    }: {
        editor: EditorState;
        presentationId: number;
        policy: LintPolicy;
        targetMinutes?: number | null;
        presentationStyle?: string;
        onEditCodeSequence: (blockId: string) => void;
    } = $props();

    const slideLint = $derived(
        lintSlide(editor.selectedSlide, policy, presentationStyle),
    );
    const deckLint = $derived(
        lintDeck(
            editor.content.slides,
            policy,
            targetMinutes,
            presentationStyle,
        ),
    );

    const verdictMessage = $derived(
        slideLint.verdict === 'over'
            ? 'Dense — consider splitting this slide.'
            : slideLint.verdict === 'warn'
              ? 'Getting full — trimming would help it breathe.'
              : slideLint.chars > 0
                ? 'Good length.'
                : 'No text yet.',
    );

    const verdictClass = $derived(
        slideLint.verdict === 'over'
            ? 'text-red-500'
            : slideLint.verdict === 'warn'
              ? 'text-amber-500'
              : 'text-muted-foreground',
    );

    const pace = $derived(
        deckLint.pace === 'over'
            ? { label: 'Over target', class: 'text-red-500' }
            : deckLint.pace === 'under'
              ? { label: 'Under target', class: 'text-amber-500' }
              : deckLint.pace === 'on'
                ? { label: 'On target', class: 'text-emerald-600' }
                : null,
    );

    let uploadingBackground = $state(false);
    let uploadingSlideImage = $state(false);
    let uploadingBlockImage = $state(false);
    let blockImageBroken = $state(false);
    let gradientModalOpen = $state(false);

    let libraryOpen = $state(false);
    // What a library pick should update; block picks capture the id up front so
    // a selection change behind the modal can't retarget the write.
    let libraryTarget = $state<'block' | 'slide-bg' | 'deck-bg' | null>(null);
    let libraryBlockId = $state<string | null>(null);

    let deleteBlockDialogOpen = $state(false);
    let blockIdDeleting = $state<string | null>(null);

    const confirmDeleteBlock = () => {
        if (blockIdDeleting !== null) {
            editor.removeBlock(blockIdDeleting);
        }

        deleteBlockDialogOpen = false;
        blockIdDeleting = null;
    };

    async function uploadBackgroundImage(event: Event) {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0];
        const currentTeam = page.props.currentTeam;

        if (!file || !currentTeam) {
            return;
        }

        uploadingBackground = true;

        try {
            const url = await uploadImage(
                PresentationBackgroundController.store({
                    current_team: currentTeam.slug,
                    presentation: presentationId,
                }).url,
                file,
            );

            if (url === null) {
                toast.error('Background image upload failed.');

                return;
            }

            editor.setBackgroundImage(url);
        } finally {
            uploadingBackground = false;
            input.value = '';
        }
    }

    async function uploadSlideBackgroundImage(event: Event) {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0];
        const currentTeam = page.props.currentTeam;

        if (!file || !currentTeam) {
            return;
        }

        uploadingSlideImage = true;

        try {
            const url = await uploadImage(
                UploadPresentationImageController({
                    current_team: currentTeam.slug,
                    presentation: presentationId,
                }).url,
                file,
            );

            if (url === null) {
                toast.error('Slide image upload failed.');

                return;
            }

            editor.setSlideBackgroundImage(url);
        } finally {
            uploadingSlideImage = false;
            input.value = '';
        }
    }

    async function replaceBlockImage(event: Event) {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0];
        const currentTeam = page.props.currentTeam;
        const targetBlock = editor.selectedBlock;

        if (!file || !currentTeam || !targetBlock) {
            return;
        }

        uploadingBlockImage = true;

        try {
            const url = await uploadImage(
                UploadPresentationImageController({
                    current_team: currentTeam.slug,
                    presentation: presentationId,
                }).url,
                file,
            );

            if (url === null) {
                toast.error('Image upload failed.');

                return;
            }

            editor.updateBlockSrc(targetBlock.id, url);
            blockImageBroken = false;
        } finally {
            uploadingBlockImage = false;
            input.value = '';
        }
    }

    function openLibrary(
        target: 'block' | 'slide-bg' | 'deck-bg',
        blockId: string | null = null,
    ) {
        libraryTarget = target;
        libraryBlockId = blockId;
        libraryOpen = true;
    }

    function onLibrarySelected(url: string) {
        if (libraryTarget === 'block' && libraryBlockId !== null) {
            editor.updateBlockSrc(libraryBlockId, url);
            blockImageBroken = false;
        } else if (libraryTarget === 'slide-bg') {
            editor.setSlideBackgroundImage(url);
        } else if (libraryTarget === 'deck-bg') {
            editor.setBackgroundImage(url);
        }

        libraryTarget = null;
        libraryBlockId = null;
    }

    async function removeBackgroundImage() {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        await fetch(
            PresentationBackgroundController.destroy({
                current_team: currentTeam.slug,
                presentation: presentationId,
            }).url,
            {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken(),
                    Accept: 'application/json',
                },
            },
        );

        editor.setBackgroundImage(null);
    }

    const fontSizes = ['1rem', '1.5rem', '2rem', '2.5rem', '3rem', '4rem'];
    const fontWeights = ['normal', 'medium', 'semibold', 'bold'];

    const block = $derived(editor.selectedBlock);

    // Re-test whether the selected image loads whenever the block or its src
    // changes; the preview's onerror/onload flips this back as needed. Reading
    // the fields into deps is what registers the effect's dependencies.
    $effect(() => {
        const deps = [block?.id, block?.src];

        if (deps) {
            blockImageBroken = false;
        }
    });

    const transitions = $derived(
        editor.transitionsForSlide(editor.selectedSlide.id),
    );
    const pinnedTransition = $derived(
        transitions.find(
            (transition) => transition.nodeId === block?.transition?.nodeId,
        ) ?? null,
    );

    // Which inspector sections are folded open. Component-level so folds
    // survive switching slides and blocks; the rarely-used background image
    // sections start closed to keep the panel scannable.
    const sectionsOpen = $state({
        transition: true,
        code: true,
        image: true,
        qr: true,
        typography: true,
        colors: true,
        slide: true,
        layout: true,
        background: true,
        slideImage: false,
        deckImage: false,
        notes: true,
    });

    const renameTransition = (nodeId: string, value: string) => {
        if (!editor.setTransitionLabel(nodeId, value.trim() || null)) {
            toast.error('Another step on this slide already has that name.');
        }
    };

    const setTransition = (blockId: string, value: string) => {
        if (value === '__new__') {
            const nodeId = editor.appendTransitionToSlide(
                editor.selectedSlide.id,
            );

            if (nodeId) {
                editor.pinBlock(blockId, nodeId);
            }

            return;
        }

        editor.pinBlock(blockId, value || null);
    };
</script>

{#snippet sectionHeader(title: string)}
    <summary
        class="flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-muted-foreground select-none hover:text-foreground [&::-webkit-details-marker]:hidden"
    >
        <ChevronRight
            class="h-3.5 w-3.5 transition-transform group-open:rotate-90"
        />
        {title}
    </summary>
{/snippet}

<div class="flex h-full w-64 flex-col gap-4 overflow-y-auto border-l p-4">
    {#if block}
        {#if block.type !== 'richtext'}
            <details
                class="group"
                bind:open={sectionsOpen.transition}
                data-test="inspector-section-transition"
            >
                {@render sectionHeader('Transition')}
                <div class="mt-2 space-y-3">
                    <div class="space-y-1">
                        <label class="label text-xs" for="block-transition"
                            >Step</label
                        >
                        <select
                            id="block-transition"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.transition?.nodeId ?? ''}
                            onchange={(event) =>
                                setTransition(
                                    block.id,
                                    event.currentTarget.value,
                                )}
                            data-test="inspector-transition"
                        >
                            <option value="">Static (always visible)</option>
                            {#each transitions as transition (transition.nodeId)}
                                <option value={transition.nodeId}>
                                    {editor.transitionDisplayName(transition)}
                                </option>
                            {/each}
                            <option value="__new__">+ New step</option>
                        </select>
                    </div>

                    {#if pinnedTransition}
                        <div class="space-y-1">
                            <label class="label text-xs" for="transition-label"
                                >Step name</label
                            >
                            <input
                                id="transition-label"
                                type="text"
                                class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                                placeholder="Step {pinnedTransition.index + 1}"
                                value={pinnedTransition.label ?? ''}
                                onchange={(event) =>
                                    renameTransition(
                                        pinnedTransition.nodeId,
                                        event.currentTarget.value,
                                    )}
                                data-test="inspector-transition-label"
                            />
                        </div>
                    {/if}
                </div>
            </details>
        {/if}

        {#if block.type === 'code'}
            <details
                class="group"
                bind:open={sectionsOpen.code}
                data-test="inspector-section-code"
            >
                {@render sectionHeader('Code')}
                <div class="mt-2 space-y-3">
                    <div class="space-y-1">
                        <label class="label text-xs" for="block-lang"
                            >Language</label
                        >
                        <select
                            id="block-lang"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.lang ?? 'typescript'}
                            onchange={(e) =>
                                editor.updateBlockLang(
                                    block.id,
                                    e.currentTarget.value,
                                )}
                            data-test="inspector-lang"
                        >
                            {#each SUPPORTED_LANGUAGES as lang (lang)}
                                <option value={lang}>{lang}</option>
                            {/each}
                        </select>
                    </div>

                    <div class="space-y-1">
                        <span class="label text-xs">Code sequence</span>
                        <Button
                            variant="base"
                            outline
                            size="sm"
                            class="w-full"
                            onclick={() => onEditCodeSequence(block.id)}
                            data-test="inspector-edit-sequence"
                        >
                            <SquarePen class="h-4 w-4" />
                            {(block.actions ?? []).length > 0
                                ? `Edit sequence (${(block.actions ?? []).length})`
                                : 'Add sequence'}
                        </Button>
                        <p class="text-[11px] text-muted-foreground">
                            Morph this code through pages during the talk, with
                            optional line highlights.
                        </p>
                    </div>
                </div>
            </details>
        {/if}

        {#if block.type === 'image'}
            <details
                class="group"
                bind:open={sectionsOpen.image}
                data-test="inspector-section-image"
            >
                {@render sectionHeader('Image')}
                <div class="mt-2 space-y-3">
                    <div class="space-y-1">
                        {#if block.src && !blockImageBroken}
                            <img
                                src={block.src}
                                alt={block.alt ?? ''}
                                class="max-h-32 w-full rounded-md border object-contain"
                                onerror={() => (blockImageBroken = true)}
                                onload={() => (blockImageBroken = false)}
                                data-test="inspector-image-preview"
                            />
                        {:else}
                            <div
                                class="flex flex-col items-center gap-1 rounded-md border border-dashed p-4 text-center"
                                data-test="inspector-image-broken"
                            >
                                <ImageOff
                                    class="h-6 w-6 text-muted-foreground"
                                />
                                <p class="text-[11px] text-muted-foreground">
                                    {block.src
                                        ? "This image couldn't be loaded. Reupload it below."
                                        : 'No image yet.'}
                                </p>
                            </div>
                        {/if}
                        <label
                            class="flex h-8 w-full cursor-pointer items-center justify-center gap-1.5 rounded-md border text-xs hover:bg-accent {uploadingBlockImage
                                ? 'pointer-events-none opacity-60'
                                : blockImageBroken || !block.src
                                  ? 'border-amber-500 text-amber-600'
                                  : ''}"
                        >
                            {uploadingBlockImage
                                ? 'Uploading…'
                                : blockImageBroken || !block.src
                                  ? 'Reupload image'
                                  : 'Replace image'}
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="hidden"
                                onchange={replaceBlockImage}
                                data-test="inspector-image-input"
                            />
                        </label>
                        <Button
                            variant="base"
                            outline
                            size="sm"
                            class="w-full"
                            onclick={() => openLibrary('block', block.id)}
                            data-test="inspector-image-library"
                        >
                            From library
                        </Button>
                    </div>
                    <div class="space-y-1">
                        <label class="label text-xs" for="block-alt"
                            >Alt text</label
                        >
                        <input
                            id="block-alt"
                            type="text"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.alt ?? ''}
                            oninput={(event) =>
                                editor.updateBlockAlt(
                                    block.id,
                                    event.currentTarget.value,
                                )}
                            data-test="inspector-alt"
                        />
                    </div>
                </div>
            </details>
        {/if}

        {#if block.type === 'qr'}
            <details
                class="group"
                bind:open={sectionsOpen.qr}
                data-test="inspector-section-qr"
            >
                {@render sectionHeader('QR code')}
                <div class="mt-2 space-y-3">
                    <div class="space-y-1">
                        <label class="label text-xs" for="qr-url"
                            >URL to encode</label
                        >
                        <input
                            id="qr-url"
                            type="text"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.src ?? ''}
                            oninput={(event) =>
                                editor.updateBlockSrc(
                                    block.id,
                                    event.currentTarget.value,
                                )}
                            placeholder="https://example.com"
                            data-test="inspector-qr-url"
                        />
                    </div>
                    <div class="space-y-1">
                        <label class="label text-xs" for="qr-size">Size</label>
                        <select
                            id="qr-size"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.alt ?? 'medium'}
                            onchange={(event) =>
                                editor.updateBlockAlt(
                                    block.id,
                                    event.currentTarget.value,
                                )}
                            data-test="inspector-qr-size"
                        >
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                        </select>
                    </div>
                </div>
            </details>
        {/if}

        {#if block.type === 'text' || block.type === 'box'}
            <details
                class="group"
                bind:open={sectionsOpen.typography}
                data-test="inspector-section-typography"
            >
                {@render sectionHeader('Typography')}
                <div class="mt-2 space-y-3">
                    <p class="text-xs text-muted-foreground">
                        Box defaults. Select text in the box to style a span
                        individually.
                    </p>
                    <div class="space-y-1">
                        <label class="label text-xs" for="block-font-size"
                            >Font size</label
                        >
                        <select
                            id="block-font-size"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.style.fontSize ?? ''}
                            onchange={(event) =>
                                editor.updateBlockStyle(block.id, {
                                    fontSize: event.currentTarget.value || null,
                                })}
                            data-test="inspector-font-size"
                        >
                            <option value="">Default</option>
                            {#each fontSizes as size (size)}
                                <option value={size}>{size}</option>
                            {/each}
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="label text-xs" for="block-font-weight"
                            >Font weight</label
                        >
                        <select
                            id="block-font-weight"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.style.fontWeight ?? ''}
                            onchange={(event) =>
                                editor.updateBlockStyle(block.id, {
                                    fontWeight:
                                        event.currentTarget.value || null,
                                })}
                            data-test="inspector-font-weight"
                        >
                            <option value="">Default</option>
                            {#each fontWeights as weight (weight)}
                                <option value={weight}>{weight}</option>
                            {/each}
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="label text-xs" for="block-font-family"
                            >Font</label
                        >
                        <select
                            id="block-font-family"
                            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm"
                            value={block.style.fontFamily ?? ''}
                            onchange={(event) =>
                                editor.updateBlockStyle(block.id, {
                                    fontFamily:
                                        event.currentTarget.value || null,
                                })}
                            data-test="inspector-font-family"
                        >
                            <option value="">Default</option>
                            {#each FONTS as font (font.label)}
                                <option
                                    value={font.label}
                                    style="font-family: {font.stack}"
                                    >{font.label}</option
                                >
                            {/each}
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="label text-xs" for="block-color"
                            >Text color</label
                        >
                        <ColorField
                            id="block-color"
                            value={block.style.color ?? '#000000'}
                            onchange={(color) =>
                                editor.updateBlockStyle(block.id, { color })}
                            dataTest="inspector-color"
                        />
                    </div>
                </div>
            </details>
        {/if}

        {#if block.type === 'box'}
            <details
                class="group"
                bind:open={sectionsOpen.colors}
                data-test="inspector-section-colors"
            >
                {@render sectionHeader('Box colors')}
                <div class="mt-2 space-y-3">
                    <div class="space-y-1">
                        <label class="label text-xs" for="block-border-color"
                            >Border color</label
                        >
                        <ColorField
                            id="block-border-color"
                            value={block.style.borderColor ?? '#e2e8f0'}
                            onchange={(borderColor) =>
                                editor.updateBlockStyle(block.id, {
                                    borderColor,
                                })}
                            dataTest="inspector-border-color"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="label text-xs" for="block-bg-color"
                            >Background</label
                        >
                        <ColorField
                            id="block-bg-color"
                            value={block.style.backgroundColor ?? '#ffffff'}
                            onchange={(backgroundColor) =>
                                editor.updateBlockStyle(block.id, {
                                    backgroundColor,
                                })}
                            dataTest="inspector-bg-color"
                        />
                    </div>
                </div>
            </details>
        {/if}

        {#if block.type === 'text' || block.type === 'box'}
            <Button
                variant="base"
                outline
                size="sm"
                onclick={() => editor.resetBlockStyleToBranding(block.id)}
                title="Reset this block's color and typography to your branding, and forget the last-used picks for this block kind"
                data-test="inspector-reset-to-branding"
            >
                <RotateCcw class="h-4 w-4" /> Reset to branding
            </Button>
        {/if}

        <Button
            variant="destructive"
            size="sm"
            onclick={() => {
                blockIdDeleting = block.id;
                deleteBlockDialogOpen = true;
            }}
            data-test="inspector-delete-block"
        >
            <Trash2 class="h-4 w-4" /> Delete block
        </Button>
    {:else}
        <details
            class="group"
            bind:open={sectionsOpen.slide}
            data-test="inspector-section-slide"
        >
            {@render sectionHeader('Slide')}
            <div class="mt-2 space-y-3">
                <div class="space-y-1">
                    <label class="label text-xs" for="slide-title">Title</label>
                    <input
                        id="slide-title"
                        type="text"
                        class="h-8 w-full rounded-md border bg-background px-2 text-sm outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Slide {editor.selectedSlideIndex + 1}"
                        value={editor.selectedSlide.title ?? ''}
                        onchange={(event) =>
                            editor.setSlideTitle(event.currentTarget.value)}
                        data-test="inspector-slide-title"
                    />
                </div>

                <div
                    class="space-y-1.5 rounded-md border p-2.5"
                    data-test="inspector-content-stats"
                >
                    <div class="flex items-center justify-between">
                        <span class="label text-xs">Content</span>
                        <span class="text-xs {verdictClass}"
                            >{verdictMessage}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between text-xs text-muted-foreground"
                    >
                        <span>
                            {#if slideLint.words > 0}{slideLint.words} words{/if}{#if slideLint.words > 0 && slideLint.cjkChars > 0},
                            {/if}{#if slideLint.cjkChars > 0}{slideLint.cjkChars}
                                chars{/if}{#if slideLint.chars === 0}Empty{/if}
                        </span>
                        <span class="font-mono tabular-nums"
                            >~{formatSpeakingTime(
                                slideLint.speakingSeconds,
                            )}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between border-t pt-1.5 text-xs"
                    >
                        <span class="text-muted-foreground">Whole deck</span>
                        <span class="font-mono tabular-nums">
                            ~{formatSpeakingTime(deckLint.totalSpeakingSeconds)}
                            {#if deckLint.targetSeconds !== null}
                                / {formatSpeakingTime(deckLint.targetSeconds)}
                            {/if}
                        </span>
                    </div>
                    {#if pace}
                        <p class="text-right text-[11px] {pace.class}">
                            {pace.label}
                        </p>
                    {/if}
                </div>

                {#if editor.isEntrySlide(editor.selectedSlide.id)}
                    <p class="text-xs text-muted-foreground">
                        Entry slide (always shown).
                    </p>
                {:else}
                    {@const enabled = editor.isSlideEnabled(
                        editor.selectedSlide.id,
                    )}
                    <div class="space-y-1">
                        <Button
                            variant={enabled ? 'base' : 'primary'}
                            outline={enabled}
                            size="sm"
                            class="w-full"
                            onclick={() =>
                                editor.toggleSlideEnabled(
                                    editor.selectedSlideIndex,
                                )}
                            data-test="inspector-toggle-slide"
                        >
                            {#if enabled}
                                <EyeOff class="h-4 w-4" /> Disable slide
                            {:else}
                                <Eye class="h-4 w-4" /> Enable slide
                            {/if}
                        </Button>
                        {#if !enabled}
                            <p class="text-xs text-muted-foreground">
                                Hidden when presenting. Enabling re-links it
                                into the flow by its order.
                            </p>
                        {/if}
                    </div>
                {/if}
            </div>
        </details>

        <details
            class="group"
            bind:open={sectionsOpen.layout}
            data-test="inspector-section-layout"
        >
            {@render sectionHeader('Layout')}
            <div class="mt-2">
                <LayoutPicker {editor} />
            </div>
        </details>

        <details
            class="group"
            bind:open={sectionsOpen.background}
            data-test="inspector-section-background"
        >
            {@render sectionHeader('Background')}
            <div class="mt-2">
                <div class="space-y-1">
                    {#if isGradientBackground(editor.selectedSlide.background)}
                        <button
                            type="button"
                            class="h-8 w-full cursor-pointer rounded-md border"
                            style="background: {editor.selectedSlide
                                .background}"
                            onclick={() => (gradientModalOpen = true)}
                            aria-label="Edit gradient"
                            data-test="inspector-background-gradient"
                        ></button>
                    {:else}
                        <ColorField
                            id="slide-background"
                            value={editor.selectedSlide.background ?? '#ffffff'}
                            onchange={(color) => editor.setBackground(color)}
                            dataTest="inspector-background"
                        />
                    {/if}
                    <div class="flex gap-1.5">
                        <Button
                            variant="base"
                            outline
                            size="sm"
                            class="flex-1"
                            onclick={() => (gradientModalOpen = true)}
                            data-test="inspector-background-gradient-open"
                        >
                            {isGradientBackground(
                                editor.selectedSlide.background,
                            )
                                ? 'Edit gradient'
                                : 'Gradient…'}
                        </Button>
                        {#if isGradientBackground(editor.selectedSlide.background)}
                            <Button
                                variant="ghost"
                                size="sm"
                                onclick={() => editor.setBackground('#ffffff')}
                                data-test="inspector-background-solid"
                            >
                                Solid
                            </Button>
                        {/if}
                    </div>
                    <Button
                        variant="base"
                        outline
                        size="sm"
                        class="w-full"
                        onclick={() => editor.applyBackgroundToAllSlides()}
                        data-test="apply-background-all"
                    >
                        Apply to all slides
                    </Button>
                </div>
            </div>
        </details>

        <GradientModal
            bind:open={gradientModalOpen}
            current={editor.selectedSlide.background}
            onSave={(gradient) => editor.setBackground(gradient)}
        />

        <details
            class="group"
            bind:open={sectionsOpen.slideImage}
            data-test="inspector-section-slide-image"
        >
            {@render sectionHeader('Background image (this slide)')}
            <div class="mt-2 space-y-1">
                {#if editor.selectedSlide.backgroundImage}
                    <div
                        class="h-16 w-full rounded-md border bg-cover bg-center"
                        style="background-image: url('{editor.selectedSlide
                            .backgroundImage}')"
                        data-test="inspector-slide-background-image-preview"
                    ></div>
                {/if}
                <label
                    class="flex h-8 w-full cursor-pointer items-center justify-center rounded-md border text-xs hover:bg-accent {uploadingSlideImage
                        ? 'pointer-events-none opacity-60'
                        : ''}"
                >
                    {uploadingSlideImage
                        ? 'Uploading…'
                        : editor.selectedSlide.backgroundImage
                          ? 'Replace image'
                          : 'Upload image'}
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="hidden"
                        onchange={uploadSlideBackgroundImage}
                        data-test="inspector-slide-background-image-input"
                    />
                </label>
                <Button
                    variant="base"
                    outline
                    size="sm"
                    class="w-full"
                    onclick={() => openLibrary('slide-bg')}
                    data-test="inspector-slide-background-library"
                >
                    From library
                </Button>
                {#if editor.selectedSlide.backgroundImage}
                    <Button
                        variant="base"
                        outline
                        size="sm"
                        class="w-full"
                        onclick={() => editor.setSlideBackgroundImage(null)}
                        data-test="remove-slide-background-image"
                    >
                        Remove image
                    </Button>
                {/if}
                <p class="text-[11px] text-muted-foreground">
                    Covers this slide only, above its background color.
                </p>
            </div>
        </details>

        <details
            class="group"
            bind:open={sectionsOpen.deckImage}
            data-test="inspector-section-deck-image"
        >
            {@render sectionHeader('Background image (all slides)')}
            <div class="mt-2 space-y-1">
                {#if editor.backgroundImage}
                    <div
                        class="h-16 w-full rounded-md border bg-cover bg-center"
                        style="background-image: url('{editor.backgroundImage}')"
                        data-test="inspector-background-image-preview"
                    ></div>
                {/if}
                <label
                    class="flex h-8 w-full cursor-pointer items-center justify-center rounded-md border text-xs hover:bg-accent {uploadingBackground
                        ? 'pointer-events-none opacity-60'
                        : ''}"
                >
                    {uploadingBackground
                        ? 'Uploading…'
                        : editor.backgroundImage
                          ? 'Replace image'
                          : 'Upload image'}
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="hidden"
                        onchange={uploadBackgroundImage}
                        data-test="inspector-background-image-input"
                    />
                </label>
                <Button
                    variant="base"
                    outline
                    size="sm"
                    class="w-full"
                    onclick={() => openLibrary('deck-bg')}
                    data-test="inspector-background-library"
                >
                    From library
                </Button>
                {#if editor.backgroundImage}
                    <Button
                        variant="base"
                        outline
                        size="sm"
                        class="w-full"
                        onclick={removeBackgroundImage}
                        data-test="remove-background-image"
                    >
                        Remove image
                    </Button>
                {/if}
                <p class="text-[11px] text-muted-foreground">
                    Shows behind every slide that has no background color of its
                    own.
                </p>
            </div>
        </details>

        <details
            class="group"
            bind:open={sectionsOpen.notes}
            data-test="inspector-section-notes"
        >
            {@render sectionHeader('Speaker notes')}
            <div class="mt-2 space-y-1">
                <textarea
                    aria-label="Speaker notes"
                    id="slide-notes"
                    rows="5"
                    class="w-full resize-y rounded-md border bg-transparent px-2.5 py-1.5 text-xs leading-relaxed focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    placeholder="Only you see these — on the phone remote while presenting."
                    value={editor.selectedSlide.notes ?? ''}
                    onchange={(event) =>
                        editor.setSlideNotes(event.currentTarget.value)}
                    data-test="inspector-slide-notes"
                ></textarea>
            </div>
        </details>

        <p class="text-xs text-muted-foreground">
            Select a block on the canvas to edit its styles.
        </p>
    {/if}
</div>

<Modal bind:open={deleteBlockDialogOpen}>
    {#snippet title()}Delete block{/snippet}
    <p class="text-muted-foreground text-sm">
        Delete this block? This cannot be undone.
    </p>
    {#snippet actions()}
        <Button
            variant="base"
            outline
            onclick={() => (deleteBlockDialogOpen = false)}
        >
            Cancel
        </Button>
        <Button
            variant="destructive"
            onclick={confirmDeleteBlock}
            data-test="inspector-delete-block-confirm"
        >
            Delete
        </Button>
    {/snippet}
</Modal>

<ImageLibraryModal bind:open={libraryOpen} onSelect={onLibrarySelected} />
