<script lang="ts">
    import { onMount } from 'svelte';
    import ExternalPresenter from '@/components/tecturn/ExternalPresenter.svelte';
    import Presenter from '@/components/tecturn/Presenter.svelte';
    import type {
        FlowGraph,
        PresentationContent,
        PresentationSource,
    } from '@/types/generated';

    let {
        content,
        flow = null,
        stepEvents = [],
        audioUrl = null,
        source = null,
        sourcePdfUrl = null,
        onSlideChange,
    }: {
        content: PresentationContent;
        flow?: FlowGraph | null;
        stepEvents?: { at_ms: number; slide: number; step: number }[];
        audioUrl?: string | null;
        source?: PresentationSource | null;
        sourcePdfUrl?: string | null;
        onSlideChange?: (current: number, total: number) => void;
    } = $props();

    // External decks (PDF / Google Slides) aren't in the frozen content snapshot,
    // so we render the live source and step it by index off the same timeline.
    const isExternal = $derived(source != null && source.type !== 'editor');
    const externalTotal = $derived(
        source?.slideCount ??
            (stepEvents.length > 0
                ? Math.max(...stepEvents.map((event) => event.slide)) + 1
                : 1),
    );
    let externalIndex = $state(0);

    let presenter = $state<Presenter>();
    let audioElement = $state<HTMLAudioElement>();
    let playing = $state(false);

    onMount(() => {
        if (isExternal) {
            onSlideChange?.(0, externalTotal);
        }
    });

    // The last position the sync loop drove to, so timeupdate only calls
    // into Reveal when the recorded position actually changed. While the
    // audio is paused the deck navigates freely.
    let syncedKey = '';

    const eventAt = (
        ms: number,
    ): { at_ms: number; slide: number; step: number } | null => {
        let match = null;

        for (const event of stepEvents) {
            if (event.at_ms > ms) {
                break;
            }

            match = event;
        }

        return match;
    };

    const syncToAudio = (): void => {
        if (!playing || !audioElement) {
            return;
        }

        const event = eventAt(audioElement.currentTime * 1000);

        if (!event) {
            return;
        }

        const key = `${event.slide}:${event.step}`;

        if (key === syncedKey) {
            return;
        }

        syncedKey = key;

        if (isExternal) {
            externalIndex = event.slide;
            onSlideChange?.(event.slide, externalTotal);
        } else {
            presenter?.navigateTo(event.slide, event.step);
        }
    };

    const canSync = $derived(audioUrl !== null && stepEvents.length > 0);
</script>

<div class="flex flex-col gap-3">
    <!-- The stage must be a query container: slide text is sized in cqw and
         otherwise falls back to viewport units, blowing up the fonts. -->
    <div
        class="relative overflow-hidden rounded-xl border border-border bg-black [container-type:size]"
        style="aspect-ratio: 16 / 9;"
    >
        {#if isExternal && source}
            <ExternalPresenter {source} {sourcePdfUrl} controlledIndex={externalIndex} />
        {:else}
            <Presenter
                bind:this={presenter}
                {content}
                {flow}
                embedded
                {onSlideChange}
            />
        {/if}
    </div>

    {#if audioUrl}
        <div class="flex flex-col gap-1">
            <audio
                bind:this={audioElement}
                src={audioUrl}
                controls
                preload="metadata"
                class="w-full"
                data-test="rehearsal-audio"
                onplay={() => {
                    playing = true;
                    // Re-sync immediately so scrubbing while paused lands on
                    // the right slide as soon as playback resumes.
                    syncedKey = '';
                    syncToAudio();
                }}
                onpause={() => {
                    playing = false;
                }}
                onended={() => {
                    playing = false;
                }}
                ontimeupdate={syncToAudio}
            ></audio>
            {#if canSync}
                <p class="text-xs text-muted-foreground">
                    Playing the recording moves the slides along the rehearsed
                    timeline; pause to browse freely.
                </p>
            {/if}
        </div>
    {/if}
</div>
