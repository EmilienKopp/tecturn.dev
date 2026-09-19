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
    import Heading from '@/components/Heading.svelte';
    import RehearsalReplay from '@/components/tecturn/RehearsalReplay.svelte';
    import { Button } from '@/components/ui/button';
    import { shownSlideTitles } from '@/lib/tecturn/flow-compiler';
    import { importJson } from '@/routes/presentations';
    import { store as storeReviewRequest } from '@/routes/rehearsals/reviews';
    import { index as reviewsIndex } from '@/routes/reviews';
    import type { FlowGraph, PresentationContent } from '@/types/generated';

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
    }: {
        run: Run;
        reviews?: Review[];
        followers?: Follower[];
        audioUrl?: string | null;
    } = $props();

    let currentSlide = $state(0);
    let slideCount = $state(run.content.slides.length);

    // Shown-order slide titles, so index N matches Reveal's slide N.
    const slideTitles = $derived(
        shownSlideTitles(
            $state.snapshot(run.content),
            $state.snapshot(run.flow),
        ),
    );
    const slideTitle = (index: number): string | null =>
        slideTitles[index] ?? null;

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
                    Slide {currentSlide + 1} / {slideCount}{slideTitle(
                        currentSlide,
                    )
                        ? ` · ${slideTitle(currentSlide)}`
                        : ''}
                </span>
            </div>
            <RehearsalReplay
                content={run.content}
                flow={run.flow}
                stepEvents={run.step_events}
                {audioUrl}
                onSlideChange={(current, total) => {
                    currentSlide = current;
                    slideCount = total;
                }}
            />
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
                                <span
                                    class="min-w-0 truncate font-medium text-foreground"
                                >
                                    Slide {timing.slide + 1}
                                    {#if slideTitle(timing.slide)}
                                        <span
                                            class="text-xs font-normal text-muted-foreground"
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

            <!-- Peer review: ask a follower to look at this run; the
                 feedback itself lives on the Reviews screen. -->
            <h2 class="mt-4 text-sm font-semibold text-foreground">
                Peer review
            </h2>

            {#if availableReviewers.length > 0}
                <div
                    class="flex flex-col gap-2 rounded-xl border border-border bg-card p-3"
                >
                    <label
                        class="text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                        for="reviewer-picker"
                    >
                        Ask a follower to review
                    </label>
                    <div class="flex gap-2">
                        <select
                            id="reviewer-picker"
                            class="min-w-0 flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
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
                            onclick={requestReview}
                            disabled={selectedReviewerId === null ||
                                requestingReview}
                            data-test="request-review"
                        >
                            {requestingReview ? 'Sending…' : 'Request'}
                        </Button>
                    </div>
                    {#if reviewErrors.reviewer_user_id}
                        <p class="text-xs text-red-500">
                            {reviewErrors.reviewer_user_id}
                        </p>
                    {/if}
                </div>
            {:else if followers.length === 0}
                <p
                    class="rounded-xl border border-dashed border-border bg-card px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    Reviews come from people who follow you. Share your profile
                    from Contacts to gather followers first.
                </p>
            {/if}

            {#if reviews.length > 0}
                <a
                    href={reviewsIndex({ query: { run: run.id } }).url}
                    class="flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-3 text-sm font-medium text-foreground hover:bg-accent"
                    data-test="rehearsal-reviews-link"
                >
                    <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    {reviews.length === 1
                        ? '1 review on this rehearsal'
                        : `${reviews.length} reviews on this rehearsal`}
                </a>
            {/if}
        </section>
    </div>
</div>
