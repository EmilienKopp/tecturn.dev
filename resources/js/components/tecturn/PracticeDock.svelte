<script module lang="ts">
    export type PracticeRunPayload = {
        started_at: string;
        ended_at: string;
        duration_seconds: number;
        slide_timings: { slide: number; seconds: number }[];
        step_events: { at_ms: number; slide: number; step: number }[];
        audio: File | null;
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
        currentStep = 0,
        saving = false,
        onFinish,
    }: {
        talkSettings: TalkSettings;
        slideCount?: number;
        currentSlide?: number;
        currentStep?: number;
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

    // Navigation timeline in active-time milliseconds. Because the recorder
    // pauses and resumes with the clock, at_ms doubles as the audio position,
    // which is what lets replay drive the slides from the recording.
    let stepEvents: { at_ms: number; slide: number; step: number }[] = [];

    const activeMs = (): number =>
        totalMs + (chunkStartedAt !== null ? Date.now() - chunkStartedAt : 0);

    // --- Voice recording ---
    // Recording is best-effort: a denied microphone never blocks the run.
    let recorder: MediaRecorder | null = null;
    let recorderChunks: Blob[] = [];
    let micDenied = $state(false);
    let recording = $state(false);

    const recorderMimeType = (): string =>
        typeof MediaRecorder !== 'undefined' &&
        MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : 'audio/mp4';

    const startRecorder = async (): Promise<void> => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            recorderChunks = [];
            recorder = new MediaRecorder(stream, {
                mimeType: recorderMimeType(),
            });
            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    recorderChunks.push(event.data);
                }
            };
            recorder.start(1000);
            recording = true;
        } catch {
            recorder = null;
            micDenied = true;
        }
    };

    const stopRecorder = (): Promise<File | null> => {
        const active = recorder;

        if (!active) {
            return Promise.resolve(null);
        }

        return new Promise((resolve) => {
            active.onstop = () => {
                const type = active.mimeType || recorderMimeType();
                const file = new File(
                    recorderChunks,
                    type.includes('mp4') ? 'rehearsal.m4a' : 'rehearsal.webm',
                    { type },
                );

                active.stream.getTracks().forEach((track) => track.stop());
                recorder = null;
                recording = false;
                resolve(recorderChunks.length > 0 ? file : null);
            };
            active.stop();
        });
    };

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

    // Leaving the page mid-run must release the microphone.
    $effect(() => {
        return () => {
            recorder?.stream.getTracks().forEach((track) => track.stop());
        };
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

    // Every position change (slide or within-slide reveal) lands on the
    // step-event timeline while running, stamped in active time.
    let trackedStepKey = $state('');

    $effect(() => {
        const key = `${currentSlide}:${currentStep}`;

        if (key !== trackedStepKey) {
            trackedStepKey = key;

            if (status === 'running') {
                stepEvents.push({
                    at_ms: activeMs(),
                    slide: currentSlide,
                    step: currentStep,
                });
            }
        }
    });

    const start = async (): Promise<void> => {
        startedAtIso = new Date().toISOString();
        totalMs = 0;
        slideMs = {};
        stepEvents = [
            { at_ms: 0, slide: currentSlide, step: currentStep },
        ];
        trackedStepKey = `${currentSlide}:${currentStep}`;
        await startRecorder();
        status = 'running';
        openChunk();
        refreshDisplay();
    };

    const pause = (): void => {
        foldChunk();

        // Pausing the recorder with the clock keeps audio time equal to
        // active time, so at_ms stays a valid audio offset.
        if (recorder?.state === 'recording') {
            recorder.pause();
        }

        status = 'paused';
        refreshDisplay();
    };

    const resume = (): void => {
        if (recorder?.state === 'paused') {
            recorder.resume();
        }

        openChunk();
        status = 'running';
    };

    const stop = async (): Promise<void> => {
        foldChunk();
        status = 'finished';
        refreshDisplay();

        const audio = await stopRecorder();

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
            step_events: stepEvents,
            audio,
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
            {:else if recording && status === 'running'}
                <span
                    class="ml-auto flex items-center gap-1.5 normal-case text-red-400"
                    data-test="practice-recording"
                >
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-red-500"
                    ></span>
                    Rec
                </span>
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
                The clock, per-slide timings and voice recording only run
                while started.
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
                Stop saves this rehearsal with a snapshot of the deck{recording
                    ? ' and the voice recording'
                    : ''}.
            </p>
            {#if micDenied}
                <p
                    class="rounded-lg bg-zinc-800 px-3 py-2 text-center text-xs text-amber-400"
                    data-test="practice-mic-denied"
                >
                    Microphone unavailable — timing without audio.
                </p>
            {/if}
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
