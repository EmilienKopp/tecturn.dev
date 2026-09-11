<script lang="ts">
    import {
        QR_SIZE_CQW,
        normalizeQrSize,
        qrToSvg,
    } from '@/lib/tecturn/CodeGeneration/qr';
    import type { EditorState } from '@/lib/tecturn/editor-state.svelte';
    import type { Block } from '@/types/generated';

    let { editor, block }: { editor: EditorState; block: Block } = $props();

    // Same generator the codegen export uses, so the canvas preview matches the
    // presented and embedded QR exactly.
    const svg = $derived(
        block.src ? qrToSvg(block.src, { title: block.src }) : '',
    );
    const widthCqw = $derived(QR_SIZE_CQW[normalizeQrSize(block.alt)]);
</script>

<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
<div
    class="flex w-full items-center justify-center rounded-md p-2 outline-none {editor.selectedBlockId ===
    block.id
        ? 'ring-1 ring-primary'
        : ''}"
    onclick={(e) => {
        e.stopPropagation();
        editor.selectedBlockId = block.id;
    }}
    data-test="qr-block-{block.id}"
>
    {#if svg}
        <div style="width: {widthCqw}cqw; max-width: 100%;">{@html svg}</div>
    {:else}
        <div
            class="rounded border border-dashed border-current/30 px-4 py-6 text-center text-xs opacity-60"
        >
            Paste a URL in the inspector
        </div>
    {/if}
</div>
