<script lang="ts">
    import BlockPinMenu from '@/components/tecturn/BlockPinMenu.svelte';
    import BoxBlockView from '@/components/tecturn/BoxBlockView.svelte';
    import CodeBlockView from '@/components/tecturn/CodeBlockView.svelte';
    import QRBlockView from '@/components/tecturn/QRBlockView.svelte';
    import TextBlockView from '@/components/tecturn/TextBlockView.svelte';
    import type { EditorState } from '@/lib/tecturn/editor-state.svelte';

    let {
        editor,
        slot: slotName,
        class: className = '',
    }: { editor: EditorState; slot: string; class?: string } = $props();

    const blocks = $derived(editor.selectedSlide.slots[slotName] ?? []);

    let slotEl = $state<HTMLDivElement | null>(null);
    let popoverVisible = $state(false);
    let popover = $state<{ top: number; left: number }>({ top: 0, left: 0 });

    function openPopover(event: MouseEvent) {
        if (!slotEl) {
            return;
        }

        const rect = slotEl.getBoundingClientRect();
        popover = {
            top: event.clientY - rect.top,
            left: event.clientX - rect.left,
        };
        popoverVisible = true;
    }

    function addBlock(type: 'text' | 'code' | 'box' | 'qr') {
        if (type === 'text') {
            editor.addTextBlock(slotName);
        } else if (type === 'code') {
            editor.addCodeBlock(slotName);
        } else if (type === 'box') {
            editor.addBoxBlock(slotName);
        } else {
            editor.addQRBlock(slotName);
        }

        popoverVisible = false;
    }
</script>

<!-- svelte-ignore a11y_click_events_have_key_events, a11y_no_static_element_interactions -->
<div
    bind:this={slotEl}
    class="relative flex flex-col gap-2 rounded border border-dashed border-current/25 p-2 {className}"
    data-test="slot-{slotName}"
    onclick={() => (popoverVisible = false)}
    ondblclick={openPopover}
>
    {#each blocks as block (block.id)}
        <BlockPinMenu {editor} {block}>
            {#if block.type === 'text'}
                <TextBlockView {editor} {block} />
            {:else if block.type === 'code'}
                <CodeBlockView {editor} {block} />
            {:else if block.type === 'box'}
                <BoxBlockView {editor} {block} />
            {:else if block.type === 'qr'}
                <QRBlockView {editor} {block} />
            {/if}
        </BlockPinMenu>
    {/each}

    {#if blocks.length === 0}
        <div
            class="pointer-events-none flex flex-1 items-center justify-center py-4 text-xs opacity-40"
        >
            Double-click to add a block
        </div>
    {/if}

    {#if popoverVisible}
        <!-- svelte-ignore a11y_no_static_element_interactions -->
        <div
            class="absolute z-50 flex gap-1 rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
            style="top: {popover.top}px; left: {popover.left}px;"
            onclick={(e) => e.stopPropagation()}
        >
            <button
                type="button"
                class="rounded px-2 py-1 text-xs hover:bg-accent"
                onclick={() => addBlock('text')}
                data-test="add-text-block-button">Text</button
            >
            <button
                type="button"
                class="rounded px-2 py-1 text-xs hover:bg-accent"
                onclick={() => addBlock('code')}
                data-test="add-code-block-button">Code</button
            >
            <button
                type="button"
                class="rounded px-2 py-1 font-mono text-xs hover:bg-accent"
                onclick={() => addBlock('box')}
                data-test="add-box-block-button">Box</button
            >
            <button
                type="button"
                class="rounded px-2 py-1 text-xs hover:bg-accent"
                onclick={() => addBlock('qr')}
                data-test="add-qr-block-button">QR</button
            >
        </div>
    {/if}
</div>
