<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { toast } from 'svelte-sonner';
    import AppHead from '@/components/AppHead.svelte';
    import CodeSequenceModal from '@/components/tecturn/CodeSequenceModal.svelte';
    import EditorToolbar from '@/components/tecturn/EditorToolbar.svelte';
    import ExternalPresenter from '@/components/tecturn/ExternalPresenter.svelte';
    import FlowCanvas from '@/components/tecturn/flow/FlowCanvas.svelte';
    import InspectorPanel from '@/components/tecturn/InspectorPanel.svelte';
    import SlideCanvas from '@/components/tecturn/SlideCanvas.svelte';
    import SlideNavigator from '@/components/tecturn/SlideNavigator.svelte';
    import { generatePresentationSvelte } from '@/lib/tecturn/codegen';
    import {
        downloadBlob,
        downloadFile,
        slugify,
    } from '@/lib/tecturn/download';
    import {
        clearEditorDraft,
        isDraftNewer,
        loadEditorDraft,
        saveEditorDraft,
    } from '@/lib/tecturn/editor-draft';
    import { EditorState } from '@/lib/tecturn/editor-state.svelte';
    import { lastUsedStyle } from '@/lib/tecturn/last-used-style.svelte';
    import { exportMethod, update } from '@/routes/presentations';
    import type {
        FlowGraph,
        PresentationContent,
        PresentationSource,
        TalkSettings,
    } from '@/types/generated';

    let {
        presentation,
        embed,
        viewerUrl,
        sourcePdfUrl = null,
    }: {
        presentation: {
            id: number;
            name: string;
            content: PresentationContent;
            talk_settings: TalkSettings;
            is_private: boolean;
            flow: FlowGraph | null;
            source: PresentationSource;
            updated_at: string | null;
        };
        embed: {
            url: string;
            tag: string;
        };
        viewerUrl: string;
        sourcePdfUrl?: string | null;
    } = $props();

    // External decks (PDF / Google Slides) reuse this whole shell but hide the
    // slide navigator, inspector, view toggle and export, and swap the slide
    // canvas for a preview of the source.
    const isExternal = presentation.source.type !== 'editor';

    // Manual slide count for external decks: persisted so the dock can show the
    // slide number (a Google Slides iframe can't be counted automatically).
    let slideCount = $state<number | null>(presentation.source.slideCount ?? null);
    let savingSlideCount = $state(false);

    const saveSlideCount = (): void => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam || slideCount === null || slideCount < 1) {
            return;
        }

        savingSlideCount = true;
        router.put(
            update({ current_team: currentTeam.slug, presentation: presentation.id })
                .url,
            { source_slide_count: slideCount },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    savingSlideCount = false;
                },
            },
        );
    };

    // The element must be block-level with a real height — Reveal.js sizes
    // itself to 100% of its container.
    const embedSnippet = `<script src="${embed.url}"></${'script'}>\n<${embed.tag} style="display: block; width: 100%; aspect-ratio: 16 / 9;"></${embed.tag}>`;

    // A newer local draft means this browser holds edits the server never
    // received (reload, crash, or auto-save off) — restore those over the
    // server copy; anything stale or in sync is discarded by the effect below.
    // Last-used style overrides are transient and per presentation.
    lastUsedStyle.scope(presentation.id);

    const draft = loadEditorDraft(presentation.id);
    const restoringDraft =
        draft !== null && isDraftNewer(draft, presentation.updated_at);

    const editor = restoringDraft
        ? new EditorState(draft.content, draft.flow)
        : new EditorState(presentation.content, presentation.flow);
    let name = $state(restoringDraft ? draft.name : presentation.name);

    if (restoringDraft) {
        editor.selectedSlideIndex = Math.min(
            draft.selectedSlideIndex,
            editor.content.slides.length - 1,
        );
        editor.selectedBlockId = draft.selectedBlockId;
        editor.dirty = true;
    }

    let view = $state<'slides' | 'flow'>('slides');
    let codeSequenceBlockId = $state<string | null>(null);

    onMount(() => {
        if (!restoringDraft) {
            return;
        }

        toast('Restored unsaved changes from this browser.', {
            action: {
                label: 'Discard',
                onClick: () => {
                    clearEditorDraft(presentation.id);
                    router.reload();
                },
            },
        });
    });

    // Persist every edit to localStorage, debounced, regardless of the
    // server-side auto-save toggle. A clean editor mirrors the server, so its
    // draft is dropped rather than kept.
    $effect(() => {
        const snapshot = {
            name,
            content: $state.snapshot(editor.content),
            flow: $state.snapshot(editor.flow),
            selectedSlideIndex: editor.selectedSlideIndex,
            selectedBlockId: editor.selectedBlockId,
        };

        if (!editor.dirty) {
            clearEditorDraft(presentation.id);

            return;
        }

        const timeout = setTimeout(() => {
            saveEditorDraft(presentation.id, snapshot);
        }, 500);

        return () => clearTimeout(timeout);
    });

    const openSlide = (slideIndex: number) => {
        if (slideIndex !== -1) {
            editor.selectSlide(slideIndex);
        }

        view = 'slides';
    };

    // Ctrl/Cmd+C copies the selected block, Ctrl/Cmd+V pastes it onto the
    // current slide. Native clipboard behavior wins while typing in a field
    // or contenteditable, or when actual text is selected on the page.
    const handleBlockClipboardKeys = (event: KeyboardEvent) => {
        if (view !== 'slides' || !(event.ctrlKey || event.metaKey)) {
            return;
        }

        const key = event.key.toLowerCase();

        if (key !== 'c' && key !== 'v') {
            return;
        }

        const target = event.target as HTMLElement | null;

        if (key === 'c') {
            const selection = window.getSelection();

            if (
                !target?.closest(
                    'input, textarea, select, [contenteditable="true"]',
                )
            ) {
                console.log(
                    'Target is an input, textarea, select, or contenteditable element.',
                );

                return;
            }

            if (
                editor.selectedBlockId &&
                (selection === null || selection.isCollapsed) &&
                editor.copyBlock(editor.selectedBlockId)
            ) {
                event.preventDefault();
            }

            return;
        }

        if (editor.pasteBlock()) {
            event.preventDefault();
        }
    };

    const exportSvelte = () => {
        // Snapshots: codegen structuredClones its inputs, which rejects
        // $state proxies.
        downloadFile(
            `${slugify(name)}.svelte`,
            generatePresentationSvelte(
                $state.snapshot(editor.content),
                $state.snapshot(editor.flow),
            ),
        );
    };

    const exportWebComponent = async () => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        const url = exportMethod(
            {
                current_team: currentTeam.slug,
                presentation: presentation.id,
            },
            { query: { format: 'web-component' } },
        ).url;

        const response = await fetch(url);

        if (!response.ok) {
            toast.error('Web component export failed.');

            return;
        }

        downloadBlob(`${slugify(name)}.js`, await response.blob());
    };

    const exportJSON = async () => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        const url = exportMethod(
            {
                current_team: currentTeam.slug,
                presentation: presentation.id,
            },
            { query: { format: 'json' } },
        ).url;

        const response = await fetch(url);

        if (!response.ok) {
            toast.error('JSON export failed.');

            return;
        }

        downloadBlob(`${slugify(name)}.json`, await response.blob());
    };
</script>

<svelte:window onkeydown={handleBlockClipboardKeys} />

<AppHead title={name} />

<div class="flex h-[calc(100vh-4rem)] flex-col">
    <EditorToolbar
        {editor}
        presentationId={presentation.id}
        talkSettings={presentation.talk_settings}
        isPrivate={presentation.is_private}
        external={isExternal}
        bind:name
        bind:view
        onExport={exportSvelte}
        onExportWebComponent={exportWebComponent}
        onExportJSON={exportJSON}
        {embedSnippet}
        {viewerUrl}
    />

    {#if isExternal}
        <div class="flex min-h-0 flex-1 flex-col bg-zinc-950">
            <div
                class="flex items-center gap-2 border-b border-zinc-800 px-6 py-2 text-sm text-zinc-300"
            >
                <label for="external-slide-count">Number of slides</label>
                <input
                    id="external-slide-count"
                    type="number"
                    min="1"
                    max="2000"
                    bind:value={slideCount}
                    onchange={saveSlideCount}
                    class="h-8 w-20 rounded-md border border-zinc-700 bg-transparent px-2 text-sm text-white"
                    data-test="external-slide-count"
                />
                {#if savingSlideCount}
                    <span class="text-xs text-zinc-500">Saving…</span>
                {/if}
                <span class="text-xs text-zinc-500">
                    Sets the slide counter on the dock and how far you can step.
                </span>
            </div>

            <div
                class="flex min-h-0 flex-1 items-center justify-center p-6 [container-type:size]"
            >
                <div
                    style="width: min(100cqw, calc(100cqh * 16 / 9)); aspect-ratio: 16 / 9;"
                >
                    <ExternalPresenter
                        source={presentation.source}
                        {sourcePdfUrl}
                    />
                </div>
            </div>
        </div>
    {:else if view === 'flow'}
        <div class="min-h-0 flex-1">
            <FlowCanvas
                {editor}
                onOpenSlide={openSlide}
                onEditCodeSequence={(blockId) =>
                    (codeSequenceBlockId = blockId)}
            />
        </div>
    {:else}
        <div class="flex min-h-0 flex-1">
            <SlideNavigator
                {editor}
                policy={page.props.lintPolicy}
                targetMinutes={presentation.talk_settings.durationMinutes}
            />
            <SlideCanvas {editor} presentationId={presentation.id} />
            <InspectorPanel
                {editor}
                presentationId={presentation.id}
                policy={page.props.lintPolicy}
                targetMinutes={presentation.talk_settings.durationMinutes}
                onEditCodeSequence={(blockId) =>
                    (codeSequenceBlockId = blockId)}
            />
        </div>
    {/if}
</div>

<CodeSequenceModal
    {editor}
    blockId={codeSequenceBlockId}
    onClose={() => (codeSequenceBlockId = null)}
/>
