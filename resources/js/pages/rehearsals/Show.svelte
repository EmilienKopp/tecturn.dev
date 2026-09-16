<script module lang="ts">
    import { index } from '@/routes/rehearsals';
    import type { Team } from '@/types';

    export const layout = (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Rehearsals',
                href: props.currentTeam ? index(props.currentTeam.slug) : '/',
            },
            { title: 'Replay', href: '#' },
        ],
    });
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Download from 'lucide-svelte/icons/download';
    import FilePlus from 'lucide-svelte/icons/file-plus';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import Presenter from '@/components/tecturn/Presenter.svelte';
    import { Button } from '@/components/ui/button';
    import { importJson } from '@/routes/presentations';
    import type { FlowGraph, PresentationContent } from '@/types/generated';

    type Run = {
        id: number;
        presentation_id: number;
        presentation_name: string;
        started_at: string;
        ended_at: string;
        duration_seconds: number;
        slide_timings: { slide: number; seconds: number }[];
        content: PresentationContent;
        flow: FlowGraph | null;
    };

    let { run }: { run: Run } = $props();

    let currentSlide = $state(0);
    let slideCount = $state(run.content.slides.length);

    const formatTime = (seconds: number): string => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;

        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    };

    const when = $derived(
        new Date(run.started_at).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }),
    );

    const timedSlides = $derived(run.slide_timings.length);

    const avgSecondsPerSlide = $derived(
        timedSlides > 0 ? Math.round(run.duration_seconds / timedSlides) : 0,
    );

    const maxSlideSeconds = $derived(
        Math.max(1, ...run.slide_timings.map((timing) => timing.seconds)),
    );

    const teamSlug = $derived(page.props.currentTeam?.slug ?? '');

    // The snapshot repackaged as the same envelope the import flow accepts,
    // so the downloaded file round-trips through "Import JSON" untouched.
    const snapshotEnvelope = (): string =>
        JSON.stringify(
            {
                name: `${run.presentation_name} (rehearsal ${new Date(run.started_at).toLocaleDateString()})`,
                content: run.content,
                flow: run.flow,
            },
            null,
            2,
        );

    const downloadSnapshot = (): void => {
        const blob = new Blob([snapshotEnvelope()], {
            type: 'application/json',
        });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');

        anchor.href = url;
        anchor.download = `${run.presentation_name.replace(/[^\w-]+/g, '-')}-rehearsal-${run.id}.json`;
        anchor.click();
        URL.revokeObjectURL(url);
    };

    let restoring = $state(false);

    // Feeds the snapshot straight into the existing import flow, which
    // creates the new deck and redirects to its editor.
    const restoreAsNewDeck = (): void => {
        restoring = true;
        router.post(
            importJson(teamSlug).url,
            { json: snapshotEnvelope() },
            {
                onFinish: () => {
                    restoring = false;
                },
            },
        );
    };

    const stats = $derived([
        { label: 'Total time', value: formatTime(run.duration_seconds) },
        { label: 'Slides in deck', value: String(run.content.slides.length) },
        { label: 'Slides visited', value: String(timedSlides) },
        { label: 'Avg per slide', value: formatTime(avgSecondsPerSlide) },
    ]);
</script>

<AppHead title="Rehearsal · {run.presentation_name}" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <Heading
            variant="small"
            title={run.presentation_name}
            description="Rehearsed {when}, with the slides as they were then"
        />
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                onclick={downloadSnapshot}
                data-test="rehearsal-download-json"
            >
                <Download class="h-4 w-4" /> Download JSON
            </Button>
            <Button
                onclick={restoreAsNewDeck}
                disabled={restoring}
                data-test="rehearsal-restore-deck"
            >
                <FilePlus class="h-4 w-4" />
                {restoring ? 'Creating…' : 'New deck from snapshot'}
            </Button>
        </div>
    </div>

    <!-- Timing summary strip, same shape as the dashboard's engagement strip -->
    <section
        class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-4"
    >
        {#each stats as stat (stat.label)}
            <div class="bg-card p-5">
                <p
                    class="font-mono text-3xl font-bold tabular-nums text-foreground"
                >
                    {stat.value}
                </p>
                <p
                    class="mt-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    {stat.label}
                </p>
            </div>
        {/each}
    </section>

    <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr]">
        <!-- Frozen deck replay -->
        <section class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-sm font-semibold text-foreground">
                    Deck at rehearsal time
                </h2>
                <span class="text-xs text-muted-foreground">
                    Slide {currentSlide + 1} / {slideCount}
                </span>
            </div>
            <!-- The stage must be a query container: slide text is sized in
                 cqw and otherwise falls back to viewport units, blowing up
                 the fonts. Mirrors the [container-type:size] column on the
                 present page. -->
            <div
                class="relative overflow-hidden rounded-xl border border-border bg-black [container-type:size]"
                style="aspect-ratio: 16 / 9;"
            >
                <Presenter
                    content={run.content}
                    flow={run.flow}
                    embedded
                    onSlideChange={(current, total) => {
                        currentSlide = current;
                        slideCount = total;
                    }}
                />
            </div>
        </section>

        <!-- Per-slide timing breakdown -->
        <section class="flex flex-col gap-3">
            <h2 class="text-sm font-semibold text-foreground">
                Time per slide
            </h2>

            {#if timedSlides > 0}
                <ul class="flex flex-col gap-2">
                    {#each run.slide_timings as timing (timing.slide)}
                        <li
                            class="rounded-xl border border-border bg-card p-3"
                            data-test="slide-timing-row"
                        >
                            <div
                                class="flex items-baseline justify-between text-sm"
                            >
                                <span class="font-medium text-foreground">
                                    Slide {timing.slide + 1}
                                </span>
                                <span
                                    class="font-mono tabular-nums text-muted-foreground"
                                >
                                    {formatTime(timing.seconds)}
                                </span>
                            </div>
                            <div
                                class="mt-2 h-1.5 overflow-hidden rounded-full bg-accent"
                            >
                                <div
                                    class="h-full rounded-full bg-amber-500"
                                    style="width: {Math.round(
                                        (timing.seconds / maxSlideSeconds) *
                                            100,
                                    )}%"
                                ></div>
                            </div>
                        </li>
                    {/each}
                </ul>
            {:else}
                <p
                    class="rounded-xl border border-dashed border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No per-slide timings were recorded for this run.
                </p>
            {/if}
        </section>
    </div>
</div>
