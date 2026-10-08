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
    import MessageSquare from 'lucide-svelte/icons/message-square';
    import AppHead from '@/components/AppHead.svelte';
    import RehearsalReplay from '@/components/tecturn/RehearsalReplay.svelte';
    import { Button } from 'daisy-svelte';
    import {
        shownSlideNotes,
        shownSlideTitles,
    } from '@/lib/tecturn/flow-compiler';
    import { store as storeDeckFromSnapshot } from '@/routes/rehearsals/deck';
    import { store as storeReviewRequest } from '@/routes/rehearsals/reviews';
    import { index as reviewsIndex } from '@/routes/reviews';
    import type {
        FlowGraph,
        PresentationContent,
        PresentationSource,
    } from '@/types/generated';

    type Run = {
        id: number;
        presentation_id: number;
        presentation_name: string;
        started_at: string;
        ended_at: string;
        duration_seconds: number;
        slide_timings: { slide: number; seconds: number }[];
        step_events: { at_ms: number; slide: number; step: number }[];
        has_recording: boolean;
        content: PresentationContent;
        flow: FlowGraph | null;
        version: string | null;
    };

    type ReviewComment = {
        id: number;
        slide_number: number;
        message: string;
        created_at: string | null;
    };

    type Review = {
        id: number;
        reviewer_user_id: number;
        reviewer_name: string;
        reviewer_avatar: string | null;
        status: string;
        requested_at: string | null;
        comments: ReviewComment[];
    };

    type Follower = {
        id: number;
        name: string;
        avatar: string | null;
        handle: string | null;
    };

    let {
        run,
        reviews = [],
        followers = [],
        audioUrl = null,
        source = null,
        sourcePdfUrl = null,
    }: {
        run: Run;
        reviews?: Review[];
        followers?: Follower[];
        audioUrl?: string | null;
        source?: PresentationSource | null;
        sourcePdfUrl?: string | null;
    } = $props();

    const isExternal = $derived(source != null && source.type !== 'editor');

    let currentSlide = $state(0);
    // External decks carry a placeholder content, so seed the count from the
    // declared slide count; RehearsalReplay corrects it as the timeline plays.
    let slideCount = $state(
        source != null && source.type !== 'editor'
            ? (source.slideCount ?? 0)
            : run.content.slides.length,
    );

    // Shown-order slide titles, so index N matches Reveal's slide N.
    const slideTitles = $derived(
        shownSlideTitles(
            $state.snapshot(run.content),
            $state.snapshot(run.flow),
        ),
    );
    const slideTitle = (index: number): string | null =>
        slideTitles[index] ?? null;

    // Shown-order speaker notes, surfaced next to the replay for the slide
    // currently on stage. External decks have no frozen content, so none.
    const slideNotes = $derived(
        isExternal
            ? []
            : shownSlideNotes(
                  $state.snapshot(run.content),
                  $state.snapshot(run.flow),
              ),
    );
    const currentNotes = $derived(slideNotes[currentSlide] ?? null);

    let replay = $state<RehearsalReplay>();

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

    // The server materializes the frozen deck as the next major version of
    // the talk, then redirects to the new deck's editor.
    const restoreAsNewDeck = (): void => {
        restoring = true;
        router.post(
            storeDeckFromSnapshot({ current_team: teamSlug, rehearsal: run.id })
                .url,
            {},
            {
                onFinish: () => {
                    restoring = false;
                },
            },
        );
    };

    const deckSlides = $derived(
        isExternal
            ? (source?.slideCount ?? timedSlides)
            : run.content.slides.length,
    );

    const stats = $derived([
        { label: 'Total time', value: formatTime(run.duration_seconds) },
        { label: 'Slides in deck', value: String(deckSlides) },
        { label: 'Slides visited', value: String(timedSlides) },
        { label: 'Avg per slide', value: formatTime(avgSecondsPerSlide) },
    ]);

    // --- Peer review ---
    const reviewedIds = $derived(
        new Set(reviews.map((review) => review.reviewer_user_id)),
    );
    const availableReviewers = $derived(
        followers.filter((follower) => !reviewedIds.has(follower.id)),
    );

    let selectedReviewerId = $state<number | null>(null);
    let requestingReview = $state(false);

    const requestReview = (): void => {
        if (selectedReviewerId === null) {
            return;
        }

        requestingReview = true;
        router.post(
            storeReviewRequest({
                current_team: teamSlug,
                rehearsal: run.id,
            }).url,
            { reviewer_user_id: selectedReviewerId },
            {
                preserveScroll: true,
                onSuccess: () => {
                    selectedReviewerId = null;
                },
                onFinish: () => {
                    requestingReview = false;
                },
            },
        );
    };

    const reviewErrors = $derived(page.props.errors ?? {});
</script>

<AppHead title="Rehearsal · {run.presentation_name}" />

<!-- Editor-shaped shell: slim header, timing rail left, stage center,
     inspector dock right. -->
<div class="flex flex-col lg:h-[calc(100vh-4rem)]">
    <header
        class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 border-b border-border px-4 py-2"
    >
        <div class="min-w-0">
            <h1
                class="flex min-w-0 items-center gap-2 font-display text-sm font-semibold text-foreground"
            >
                <span class="truncate">{run.presentation_name}</span>
                {#if run.version}
                    <span
                        class="shrink-0 rounded-full bg-muted px-2 py-0.5 font-mono text-[10px] font-medium text-muted-foreground"
                    >
                        v{run.version}
                    </span>
                {/if}
            </h1>
            <p class="text-xs text-muted-foreground">
                Rehearsed {when}, with the slides as they were then
            </p>
        </div>
        <span class="font-mono text-xs tabular-nums text-muted-foreground">
            Slide {currentSlide + 1} / {slideCount}{slideTitle(currentSlide)
                ? ` · ${slideTitle(currentSlide)}`
                : ''}
        </span>
    </header>

    <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
        <!-- Left rail: per-slide timings; a row click jumps the replay. -->
        <aside
            class="order-2 shrink-0 overflow-y-auto border-border p-3 lg:order-1 lg:w-56 lg:border-r"
        >
            <h2
                class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
            >
                Time per slide
            </h2>

            {#if timedSlides > 0}
                <ul class="flex flex-col gap-1.5">
                    {#each run.slide_timings as timing (timing.slide)}
                        <li data-test="slide-timing-row">
                            <button
                                type="button"
                                class="w-full rounded-md border p-2 text-left transition-colors hover:bg-accent {timing.slide ===
                                currentSlide
                                    ? 'border-primary/60 bg-accent'
                                    : 'border-border'}"
                                onclick={() =>
                                    replay?.navigateToSlide(timing.slide)}
                            >
                                <div
                                    class="flex items-baseline justify-between gap-2 text-xs"
                                >
                                    <span
                                        class="min-w-0 truncate font-medium text-foreground"
                                    >
                                        {timing.slide + 1}
                                        {#if slideTitle(timing.slide)}
                                            <span
                                                class="font-normal text-muted-foreground"
                                            >
                                                · {slideTitle(timing.slide)}
                                            </span>
                                        {/if}
                                    </span>
                                    <span
                                        class="font-mono tabular-nums text-muted-foreground"
                                    >
                                        {formatTime(timing.seconds)}
                                    </span>
                                </div>
                                <div
                                    class="mt-1.5 h-1 overflow-hidden rounded-full bg-accent"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary"
                                        style="width: {Math.round(
                                            (timing.seconds / maxSlideSeconds) *
                                                100,
                                        )}%"
                                    ></div>
                                </div>
                            </button>
                        </li>
                    {/each}
                </ul>
            {:else}
                <p
                    class="rounded-md border border-dashed border-border px-3 py-6 text-center text-xs text-muted-foreground"
                >
                    No per-slide timings were recorded for this run.
                </p>
            {/if}
        </aside>

        <!-- Center stage: the frozen deck, as big as the room allows. -->
        <main
            class="order-1 flex min-h-0 flex-1 items-center justify-center bg-sidebar p-4 [container-type:size] max-lg:aspect-video lg:order-2 lg:p-6"
        >
            <div
                style="width: min(100cqw - 2rem, calc((100cqh - {audioUrl
                    ? '8rem'
                    : '2rem'}) * 16 / 9));"
            >
                <RehearsalReplay
                    bind:this={replay}
                    content={run.content}
                    flow={run.flow}
                    stepEvents={run.step_events}
                    {audioUrl}
                    {source}
                    {sourcePdfUrl}
                    onSlideChange={(current, total) => {
                        currentSlide = current;
                        slideCount = total;
                    }}
                />
            </div>
        </main>

        <!-- Right dock: notes for the slide on stage, the run's numbers,
             and everything you can do with this rehearsal. -->
        <aside
            class="order-3 flex shrink-0 flex-col gap-5 overflow-y-auto border-border p-4 lg:w-72 lg:border-l"
        >
            {#if !isExternal}
                <section class="flex flex-col gap-2">
                    <h2
                        class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        Slide notes
                    </h2>
                    {#if currentNotes}
                        <p
                            class="whitespace-pre-wrap rounded-md border border-border bg-card p-3 text-sm text-foreground"
                            data-test="rehearsal-slide-notes"
                        >
                            {currentNotes}
                        </p>
                    {:else}
                        <p class="text-xs text-muted-foreground">
                            No notes on this slide.
                        </p>
                    {/if}
                </section>
            {/if}

            <section class="flex flex-col gap-2">
                <h2
                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Run stats
                </h2>
                <dl
                    class="grid grid-cols-2 gap-px overflow-hidden rounded-md border border-border bg-border"
                >
                    {#each stats as stat (stat.label)}
                        <div class="bg-card px-3 py-2">
                            <dd
                                class="font-mono text-sm font-semibold tabular-nums text-foreground"
                            >
                                {stat.value}
                            </dd>
                            <dt
                                class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground"
                            >
                                {stat.label}
                            </dt>
                        </div>
                    {/each}
                </dl>
            </section>

            <section class="flex flex-col gap-2">
                <h2
                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Commands
                </h2>
                <Button
                    variant="base"
                    outline
                    size="sm"
                    class="w-full justify-start"
                    onclick={downloadSnapshot}
                    data-test="rehearsal-download-json"
                >
                    <Download class="h-4 w-4" /> Download JSON
                </Button>
                <Button
                    variant="base"
                    outline
                    size="sm"
                    class="w-full justify-start"
                    onclick={restoreAsNewDeck}
                    disabled={restoring}
                    data-test="rehearsal-restore-deck"
                >
                    <FilePlus class="h-4 w-4" />
                    {restoring ? 'Creating…' : 'New deck from snapshot'}
                </Button>
            </section>

            <!-- Peer review: ask a follower to look at this run; the
                 feedback itself lives on the Reviews screen. -->
            <section class="flex flex-col gap-2">
                <h2
                    class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Peer review
                </h2>

                {#if availableReviewers.length > 0}
                    <div
                        class="flex flex-col gap-2 rounded-md border border-border bg-card p-3"
                    >
                        <label
                            class="text-xs text-muted-foreground"
                            for="reviewer-picker"
                        >
                            Ask a follower to review
                        </label>
                        <select
                            id="reviewer-picker"
                            class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground"
                            bind:value={selectedReviewerId}
                            data-test="reviewer-picker"
                        >
                            <option value={null}>Pick a follower…</option>
                            {#each availableReviewers as follower (follower.id)}
                                <option value={follower.id}>
                                    {follower.name}{follower.handle
                                        ? ` (@${follower.handle})`
                                        : ''}
                                </option>
                            {/each}
                        </select>
                        <Button
                            size="sm"
                            onclick={requestReview}
                            disabled={selectedReviewerId === null ||
                                requestingReview}
                            data-test="request-review"
                        >
                            {requestingReview ? 'Sending…' : 'Request review'}
                        </Button>
                        {#if reviewErrors.reviewer_user_id}
                            <p class="text-xs text-destructive">
                                {reviewErrors.reviewer_user_id}
                            </p>
                        {/if}
                    </div>
                {:else if followers.length === 0}
                    <p
                        class="rounded-md border border-dashed border-border px-3 py-4 text-center text-xs text-muted-foreground"
                    >
                        Reviews come from people who follow you. Share your
                        profile from Contacts to gather followers first.
                    </p>
                {/if}

                {#if reviews.length > 0}
                    <a
                        href={reviewsIndex({ query: { run: run.id } }).url}
                        class="flex items-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground hover:bg-accent"
                        data-test="rehearsal-reviews-link"
                    >
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                        {reviews.length === 1
                            ? '1 review on this rehearsal'
                            : `${reviews.length} reviews on this rehearsal`}
                    </a>
                {/if}
            </section>
        </aside>
    </div>
</div>
