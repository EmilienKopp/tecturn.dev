<script lang="ts">
    import Copy from 'lucide-svelte/icons/copy';
    import EyeOff from 'lucide-svelte/icons/eye-off';
    import Plus from 'lucide-svelte/icons/plus';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import {
        formatSpeakingTime,
        lintDeck,
        lintSlide,
    } from '@/lib/tecturn/CodeGeneration/lint';
    import type { EditorState } from '@/lib/tecturn/editor-state.svelte';
    import type { LintPolicy } from '@/types/generated';
    import SlideLintBadge from './SlideLintBadge.svelte';

    let {
        editor,
        policy,
        targetMinutes = null,
    }: {
        editor: EditorState;
        policy: LintPolicy;
        targetMinutes?: number | null;
    } = $props();

    const deck = $derived(
        lintDeck(editor.content.slides, policy, targetMinutes),
    );

    const paceLabel = $derived(
        deck.pace === 'over'
            ? 'over target'
            : deck.pace === 'under'
              ? 'under target'
              : deck.pace === 'on'
                ? 'on target'
                : null,
    );

    const paceClass = $derived(
        deck.pace === 'over'
            ? 'text-red-500'
            : deck.pace === 'under'
              ? 'text-amber-500'
              : 'text-emerald-600',
    );

    let deleteDialogOpen = $state(false);
    let slideIndexDeleting = $state<number | null>(null);

    const slideNameDeleting = $derived(
        slideIndexDeleting !== null
            ? (editor.content.slides[slideIndexDeleting]?.title ??
                  `Slide ${slideIndexDeleting + 1}`)
            : '',
    );

    const confirmDeleteSlide = () => {
        if (slideIndexDeleting !== null) {
            editor.removeSlide(slideIndexDeleting);
        }

        deleteDialogOpen = false;
        slideIndexDeleting = null;
    };
</script>

<div class="flex h-full w-48 flex-col border-r">
    <div class="flex-1 space-y-2 overflow-y-auto p-3">
        {#each editor.content.slides as slide, index (slide.id)}
            {@const disabled = !editor.isSlideEnabled(slide.id)}
            {@const lint = lintSlide(slide, policy)}

            <button
                type="button"
                class="group relative block w-full rounded-md border p-2 text-left text-sm transition-colors hover:bg-accent {index ===
                editor.selectedSlideIndex
                    ? 'border-primary bg-accent'
                    : ''} {disabled ? 'opacity-45' : ''}"
                onclick={() => editor.selectSlide(index)}
                data-test="slide-navigator-item"
                data-disabled={disabled}
            >
                <span class="block truncate pr-5 font-medium"
                    >{slide.title ?? `Slide ${index + 1}`}</span
                >

                {#if lint.chars > 0}
                    <span class="mt-0.5 block">
                        <SlideLintBadge {lint} />
                    </span>
                {/if}

                {#if disabled}
                    <span
                        class="mt-0.5 flex items-center gap-1 text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        <EyeOff class="h-3 w-3" /> Disabled
                    </span>
                {/if}

                <div class="absolute top-1 right-1 hidden group-hover:flex gap-0.5">
                    <span
                        role="button"
                        tabindex="-1"
                        class="rounded p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        onclick={(event) => {
                            event.stopPropagation();
                            editor.duplicateSlide(index);
                        }}
                        onkeydown={() => {}}
                        aria-label="Duplicate slide {index + 1}"
                        data-test="slide-duplicate-button"
                    >
                        <Copy class="h-3 w-3" />
                    </span>
                    {#if editor.content.slides.length > 1}
                        <span
                            role="button"
                            tabindex="-1"
                            class="rounded p-1 text-muted-foreground hover:bg-accent hover:text-destructive"
                            onclick={(event) => {
                                event.stopPropagation();
                                slideIndexDeleting = index;
                                deleteDialogOpen = true;
                            }}
                            onkeydown={() => {}}
                            aria-label="Delete slide {index + 1}"
                            data-test="slide-delete-button"
                        >
                            <Trash2 class="h-3 w-3" />
                        </span>
                    {/if}
                </div>
            </button>
        {/each}
    </div>

    <div class="space-y-2 border-t p-3">
        <div
            class="flex items-baseline justify-between text-xs"
            data-test="deck-lint-total"
        >
            <span class="text-muted-foreground">Est. talk</span>
            <span class="font-mono tabular-nums">
                ~{formatSpeakingTime(deck.totalSpeakingSeconds)}
                {#if paceLabel}
                    <span class={paceClass}>· {paceLabel}</span>
                {/if}
            </span>
        </div>
        <Button
            variant="outline"
            size="sm"
            class="w-full"
            onclick={() => editor.addSlide()}
            data-test="slide-add-button"
        >
            <Plus class="h-4 w-4" /> Add slide
        </Button>
    </div>
</div>

<Dialog bind:open={deleteDialogOpen}>
    <DialogContent>
        <div class="space-y-3">
            <DialogTitle>Delete slide</DialogTitle>
            <DialogDescription>
                Delete "{slideNameDeleting}"? Its blocks and flow connections go
                with it. This cannot be undone.
            </DialogDescription>
        </div>
        <DialogFooter>
            <Button
                variant="outline"
                onclick={() => (deleteDialogOpen = false)}
            >
                Cancel
            </Button>
            <Button
                variant="destructive"
                onclick={confirmDeleteSlide}
                data-test="slide-delete-confirm"
            >
                Delete
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
