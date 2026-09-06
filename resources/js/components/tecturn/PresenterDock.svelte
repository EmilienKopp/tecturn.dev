<script lang="ts">
    import Check from 'lucide-svelte/icons/check';
    import Copy from 'lucide-svelte/icons/copy';
    import Users from 'lucide-svelte/icons/users';
    import type { TalkSettings } from '@/types/generated';
    import PresentFooter from './PresentFooter.svelte';

    let {
        viewerUrl,
        talkSettings,
        slideCount = 0,
        currentSlide = 0,
        recentReactions = [],
        viewerCount = 0,
        reactionTotal = 0,
        showReactions = $bindable(false),
    }: {
        viewerUrl: string;
        talkSettings: TalkSettings;
        slideCount?: number;
        currentSlide?: number;
        recentReactions?: { id: number; emoji: string }[];
        viewerCount?: number;
        reactionTotal?: number;
        showReactions?: boolean;
    } = $props();

    // --- Timer ---
    let elapsedSeconds = $state(0);
    let startedAt = $state(Date.now());

    // Per-slide stopwatch: reset every time the presenter moves to a new slide.
    let slideStartedAt = $state(Date.now());
    let slideElapsedSeconds = $state(0);

    $effect(() => {
        startedAt = Date.now();
        elapsedSeconds = 0;

        const interval = setInterval(() => {
            elapsedSeconds = Math.floor((Date.now() - startedAt) / 1000);
            slideElapsedSeconds = Math.floor(
                (Date.now() - slideStartedAt) / 1000,
            );
        }, 1000);

        return () => clearInterval(interval);
    });

    // Restart the per-slide stopwatch whenever the current slide changes.
    let trackedSlide = $state(-1);

    $effect(() => {
        if (currentSlide !== trackedSlide) {
            trackedSlide = currentSlide;
            slideStartedAt = Date.now();
            slideElapsedSeconds = 0;
        }
    });

    // Even split of the target across the shown slides. Null when no target is
    // set, which hides the per-slide pacing entirely.
    const slideBudgetSeconds = $derived(
        talkSettings.durationMinutes && slideCount > 0
            ? Math.round((talkSettings.durationMinutes * 60) / slideCount)
            : null,
    );

    // Green while there's room, amber past 80% of the slide's share, red once
    // it runs over — the same restraint as the deck-level linter.
    const slidePaceClass = $derived.by(() => {
        if (!slideBudgetSeconds) {
            return 'text-zinc-400';
        }

        const ratio = slideElapsedSeconds / slideBudgetSeconds;

        if (ratio > 1.1) {
            return 'text-red-400';
        }

        return ratio > 0.8 ? 'text-amber-400' : 'text-emerald-400';
    });

    const formatTime = (seconds: number): string => {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;

        if (h > 0) {
            return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    };

    const displayTime = $derived(
        talkSettings.timerMode === 'countdown' && talkSettings.durationMinutes
            ? formatTime(
                  Math.max(
                      0,
                      talkSettings.durationMinutes * 60 - elapsedSeconds,
                  ),
              )
            : formatTime(elapsedSeconds),
    );

    const isOverTime = $derived(
        talkSettings.timerMode === 'countdown' &&
            talkSettings.durationMinutes !== null &&
            talkSettings.durationMinutes !== undefined &&
            elapsedSeconds >= talkSettings.durationMinutes * 60,
    );

    // --- QR Code ---
    const qrUrl = $derived(
        `https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=4&data=${encodeURIComponent(viewerUrl)}`,
    );

    let copiedViewerUrl = $state(false);

    const copyViewerUrl = async () => {
        await navigator.clipboard.writeText(viewerUrl);
        copiedViewerUrl = true;
        setTimeout(() => (copiedViewerUrl = false), 2000);
    };
</script>

<aside
    class="flex h-full w-72 flex-col gap-4 overflow-y-auto bg-zinc-900 p-4 text-white"
    data-test="presenter-dock"
>
    <!-- Timer -->
    <section class="rounded-lg bg-zinc-800 p-4">
        <p
            class="mb-1 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            {talkSettings.timerMode === 'countdown' ? 'Time left' : 'Elapsed'}
        </p>
        <p
            class="font-mono text-4xl font-bold tabular-nums {isOverTime
                ? 'text-red-400'
                : 'text-white'}"
        >
            {displayTime}
        </p>
        {#if slideCount > 0}
            <div class="mt-2 flex items-baseline justify-between text-xs">
                <span class="text-zinc-400">
                    Slide {currentSlide + 1} / {slideCount}
                </span>
                {#if slideBudgetSeconds}
                    <span
                        class="font-mono tabular-nums {slidePaceClass}"
                        data-test="dock-slide-pace"
                    >
                        {formatTime(slideElapsedSeconds)} / {formatTime(
                            slideBudgetSeconds,
                        )}
                    </span>
                {/if}
            </div>
        {/if}
    </section>

    <!-- Live audience -->
    <section class="rounded-lg bg-zinc-800 p-4">
        <p
            class="mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            <Users class="h-3.5 w-3.5" />
            Watching now
        </p>
        <div class="flex items-baseline gap-2">
            <span
                class="font-mono text-4xl font-bold tabular-nums text-white"
                data-test="dock-viewer-count"
            >
                {viewerCount}
            </span>
            {#if viewerCount > 0}
                <span class="relative flex h-2 w-2">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                    ></span>
                </span>
            {/if}
        </div>
    </section>

    <!-- QR Code -->
    <section class="rounded-lg bg-white p-3">
        <p class="mb-2 text-center text-xs font-semibold text-zinc-700">
            Scan to react
        </p>
        <div class="flex justify-center">
            <img
                src={qrUrl}
                alt="QR code for {viewerUrl}"
                width="180"
                height="180"
            />
        </div>
        <button
            type="button"
            class="mt-2 flex w-full items-center justify-center gap-1 rounded px-1 py-0.5 text-[10px] text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600"
            onclick={copyViewerUrl}
            title="Copy reaction URL"
            data-test="dock-copy-viewer-url"
        >
            {#if copiedViewerUrl}
                <Check class="h-3 w-3 shrink-0 text-emerald-500" /> Copied!
            {:else}
                <Copy class="h-3 w-3 shrink-0" />
                <span class="truncate">{viewerUrl}</span>
            {/if}
        </button>
    </section>

    <!-- Reactions -->
    <section class="rounded-lg bg-zinc-800 p-4">
        <div class="flex items-center justify-between">
            <p
                class="text-xs font-semibold uppercase tracking-wider text-zinc-400"
            >
                Reactions
                <span class="ml-1 font-mono tabular-nums text-zinc-500"
                    >{reactionTotal}</span
                >
            </p>
            <button
                type="button"
                role="switch"
                aria-checked={showReactions}
                aria-label="Show audience reactions on screen"
                class="relative h-5 w-9 rounded-full transition-colors {showReactions
                    ? 'bg-amber-500'
                    : 'bg-zinc-600'}"
                onclick={() => (showReactions = !showReactions)}
                data-test="dock-reactions-toggle"
            >
                <span
                    class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white transition-transform {showReactions
                        ? 'translate-x-4'
                        : ''}"
                ></span>
            </button>
        </div>
    </section>

    <!-- Footer (when relocated into the dock) -->
    {#if talkSettings.footer.enabled && talkSettings.footer.showInDock}
        <div class="mt-auto">
            <PresentFooter footer={talkSettings.footer} variant="dock" />
        </div>
    {/if}
</aside>
