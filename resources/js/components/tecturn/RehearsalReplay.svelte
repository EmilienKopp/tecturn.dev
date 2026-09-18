<script lang="ts">
    import Presenter from '@/components/tecturn/Presenter.svelte';
    import type { FlowGraph, PresentationContent } from '@/types/generated';

    let {
        content,
        flow = null,
        stepEvents = [],
        audioUrl = null,
        onSlideChange,
    }: {
        content: PresentationContent;
        flow?: FlowGraph | null;
        stepEvents?: { at_ms: number; slide: number; step: number }[];
        audioUrl?: string | null;
        onSlideChange?: (current: number, total: number) => void;
    } = $props();

    let presenter = $state<Presenter>();
    let audioElement = $state<HTMLAudioElement>();
    let playing = $state(false);

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
        if (!playing || !audioElement || !presenter) {
            return;
        }

        const event = eventAt(audioElement.currentTime * 1000);

        if (!event) {
            return;
        }

        const key = `${event.slide}:${event.step}`;

        if (key !== syncedKey) {
            syncedKey = key;
            presenter.navigateTo(event.slide, event.step);
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
        <Presenter
            bind:this={presenter}
            {content}
            {flow}
            embedded
            {onSlideChange}
        />
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
