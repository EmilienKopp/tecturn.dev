<script module lang="ts">
    export type PracticeRunPayload = {
        started_at: string;
        ended_at: string;
        duration_seconds: number;
        slide_timings: { slide: number; seconds: number }[];
    };
</script>

<script lang="ts">
    import Pause from 'lucide-svelte/icons/pause';
    import Play from 'lucide-svelte/icons/play';
    import Square from 'lucide-svelte/icons/square';
    import Timer from 'lucide-svelte/icons/timer';
    import type { TalkSettings } from '@/types/generated';

    let {
        talkSettings,
        slideCount = 0,
        currentSlide = 0,
        saving = false,
        onFinish,
    }: {
        talkSettings: TalkSettings;
        slideCount?: number;
        currentSlide?: number;
        saving?: boolean;
        onFinish: (run: PracticeRunPayload) => void;
    } = $props();

    // --- Run state machine ---
    // The clock only accrues while `running`; paused stretches never count
    // toward the total or the per-slide timings.
    let status = $state<'idle' | 'running' | 'paused' | 'finished'>('idle');
    let startedAtIso = $state<string | null>(null);

    // Accrued active milliseconds, total and per slide index. The open chunk
    // (since the last start/resume/slide change) lives in chunkStartedAt and
    // is folded in on pause, stop, or slide change.
    let totalMs = 0;
    let slideMs: Record<number, number> = {};
    let chunkStartedAt: number | null = null;
    let chunkSlide = 0;

    // Display values, ticked by the interval below.
    let elapsedSeconds = $state(0);
    let slideElapsedSeconds = $state(0);

    const foldChunk = (): void => {
        if (chunkStartedAt === null) {
            return;
        }

        const chunk = Date.now() - chunkStartedAt;
        totalMs += chunk;
        slideMs[chunkSlide] = (slideMs[chunkSlide] ?? 0) + chunk;
        chunkStartedAt = null;
    };

    const openChunk = (): void => {
        chunkSlide = currentSlide;
        chunkStartedAt = Date.now();
    };

    const refreshDisplay = (): void => {
        const live = chunkStartedAt !== null ? Date.now() - chunkStartedAt : 0;
        const liveSlide =
            chunkStartedAt !== null && chunkSlide === currentSlide ? live : 0;

        elapsedSeconds = Math.floor((totalMs + live) / 1000);
        slideElapsedSeconds = Math.floor(
            ((slideMs[currentSlide] ?? 0) + liveSlide) / 1000,
        );
    };

    $effect(() => {
        const interval = setInterval(refreshDisplay, 500);

        return () => clearInterval(interval);
    });

    // Moving to another slide closes the current slide's chunk and opens a new
    // one, so each slide accrues exactly the time it was on screen.
    let trackedSlide = $state(-1);

    $effect(() => {
        if (currentSlide !== trackedSlide) {
            trackedSlide = currentSlide;

            if (status === 'running') {
                foldChunk();
                openChunk();
            }

            refreshDisplay();
        }
    });

    const start = (): void => {
        startedAtIso = new Date().toISOString();
        totalMs = 0;
        slideMs = {};
        status = 'running';
        openChunk();
        refreshDisplay();
    };

    const pause = (): void => {
        foldChunk();
        status = 'paused';
        refreshDisplay();
    };

    const resume = (): void => {
        openChunk();
        status = 'running';
    };

    const stop = (): void => {
        foldChunk();
        status = 'finished';
        refreshDisplay();

        const slide_timings = Object.entries(slideMs)
            .map(([slide, ms]) => ({
                slide: Number(slide),
                seconds: Math.round(ms / 1000),
            }))
            .sort((a, b) => a.slide - b.slide);

        onFinish({
            started_at: startedAtIso ?? new Date().toISOString(),
            ended_at: new Date().toISOString(),
            duration_seconds: Math.round(totalMs / 1000),
            slide_timings,
        });
    };

    // Same per-slide pacing as the live dock: an even split of the talk
    // target, green under 80%, amber to 110%, red beyond.
    const slideBudgetSeconds = $derived(
        talkSettings.durationMinutes && slideCount > 0
            ? Math.round((talkSettings.durationMinutes * 60) / slideCount)
            : null,
    );

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

    const isOverTime = $derived(
        talkSettings.durationMinutes !== null &&
            talkSettings.durationMinutes !== undefined &&
            elapsedSeconds >= talkSettings.durationMinutes * 60,
    );
</script>

<aside
    class="flex h-full w-72 flex-col gap-4 overflow-y-auto bg-zinc-900 p-4 text-white"
    data-test="practice-dock"
>
    <!-- Rehearsal timer -->
    <section class="rounded-lg bg-zinc-800 p-4">
        <p
            class="mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-zinc-400"
        >
            <Timer class="h-3.5 w-3.5" />
            Practice
            {#if status === 'paused'}
                <span class="ml-auto normal-case text-amber-400">Paused</span>
            {/if}
        </p>
        <p
            class="font-mono text-4xl font-bold tabular-nums {isOverTime
                ? 'text-red-400'
                : 'text-white'}"
            data-test="practice-elapsed"
        >
            {formatTime(elapsedSeconds)}
        </p>
        {#if slideCount > 0}
            <div class="mt-2 flex items-baseline justify-between text-xs">
                <span class="text-zinc-400">
                    Slide {currentSlide + 1} / {slideCount}
                </span>
                {#if slideBudgetSeconds}
                    <span
                        class="font-mono tabular-nums {slidePaceClass}"
                        data-test="practice-slide-pace"
                    >
                        {formatTime(slideElapsedSeconds)} / {formatTime(
                            slideBudgetSeconds,
                        )}
                    </span>
                {:else}
                    <span class="font-mono tabular-nums text-zinc-400">
                        {formatTime(slideElapsedSeconds)}
                    </span>
                {/if}
            </div>
        {/if}
    </section>

    <!-- Controls -->
    <section class="flex flex-col gap-2">
        {#if status === 'idle'}
            <button
                type="button"
                class="flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-3 font-semibold text-zinc-950 transition-colors hover:bg-amber-400"
                onclick={start}
                data-test="practice-start"
            >
                <Play class="h-4 w-4" /> Start rehearsal
            </button>
            <p class="text-center text-xs text-zinc-500">
                The clock and per-slide timings only run while started.
            </p>
        {:else if status === 'running' || status === 'paused'}
            <div class="flex gap-2">
                {#if status === 'running'}
                    <button
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-zinc-700 px-4 py-3 font-semibold text-white transition-colors hover:bg-zinc-600"
                        onclick={pause}
                        data-test="practice-pause"
                    >
                        <Pause class="h-4 w-4" /> Pause
                    </button>
                {:else}
                    <button
                        type="button"
                        class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-3 font-semibold text-zinc-950 transition-colors hover:bg-amber-400"
                        onclick={resume}
                        data-test="practice-resume"
                    >
                        <Play class="h-4 w-4" /> Resume
                    </button>
                {/if}
                <button
                    type="button"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-500/90 px-4 py-3 font-semibold text-white transition-colors hover:bg-red-500"
                    onclick={stop}
                    data-test="practice-stop"
                >
                    <Square class="h-4 w-4" /> Stop
                </button>
            </div>
            <p class="text-center text-xs text-zinc-500">
                Stop saves this rehearsal with a snapshot of the deck.
            </p>
        {:else}
            <div
                class="rounded-lg bg-zinc-800 px-4 py-3 text-center text-sm text-zinc-300"
                data-test="practice-saving"
            >
                {saving ? 'Saving rehearsal…' : 'Rehearsal saved.'}
            </div>
        {/if}
    </section>
</aside>
