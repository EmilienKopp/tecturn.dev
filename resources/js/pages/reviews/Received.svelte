<script module lang="ts">
    import { index } from '@/routes/reviews';

    export const layout = {
        breadcrumbs: [
            { title: 'Reviews', href: index().url },
            { title: 'Received', href: '#' },
        ],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import MessageSquare from 'lucide-svelte/icons/message-square';
    import Timer from 'lucide-svelte/icons/timer';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import RehearsalReplay from '@/components/tecturn/RehearsalReplay.svelte';
    import { Button } from '@/components/ui/button';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { shownSlideTitles } from '@/lib/tecturn/flow-compiler';
    import { received } from '@/routes/reviews';
    import type { FlowGraph, PresentationContent } from '@/types/generated';

    type ReviewComment = {
        id: number;
        slide_number: number;
        message: string;
        created_at: string | null;
    };

    type Review = {
        id: number;
        practice_run_id: number;
        presentation_id: number;
        status: string;
        presentation_name: string;
        reviewer_name: string;
        reviewer_avatar: string | null;
        rehearsed_at: string;
        duration_seconds: number;
        content: PresentationContent;
        flow: FlowGraph | null;
        step_events: { at_ms: number; slide: number; step: number }[];
        has_recording: boolean;
        comments: ReviewComment[];
    };

    type SiblingReview = {
        id: number;
        reviewer_name: string;
        reviewer_avatar: string | null;
        status: string;
        requested_at: string | null;
    };

    type OtherRun = {
        practice_run_id: number;
        rehearsed_at: string;
        duration_seconds: number;
        reviews: {
            id: number;
            reviewer_name: string;
            reviewer_avatar: string | null;
            status: string;
        }[];
    };

    let {
        review,
        audioUrl = null,
        siblingReviews = [],
        otherRuns = [],
    }: {
        review: Review;
        audioUrl?: string | null;
        siblingReviews?: SiblingReview[];
        otherRuns?: OtherRun[];
    } = $props();

    let currentSlide = $state(0);
    let slideCount = $state(review.content.slides.length);

    // Shown-order slide titles, so index N matches Reveal's slide N.
    const slideTitles = $derived(
        shownSlideTitles(
            $state.snapshot(review.content),
            $state.snapshot(review.flow),
        ),
    );
    const slideTitle = (index: number): string | null =>
        slideTitles[index] ?? null;

    const completed = $derived(review.status === 'completed');

    const when = $derived(
        new Date(review.rehearsed_at).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }),
    );

    const formatDate = (value: string): string =>
        new Date(value).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });

    const currentSlideComments = $derived(
        review.comments.filter(
            (comment) => comment.slide_number === currentSlide,
        ),
    );
    const otherComments = $derived(
        review.comments.filter(
            (comment) => comment.slide_number !== currentSlide,
        ),
    );

    const openReview = (id: number): void => {
        router.visit(received(id).url);
    };
</script>

<AppHead title="Review received · {review.presentation_name}" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-center gap-4">
            <UserAvatar
                name={review.reviewer_name}
                avatar={review.reviewer_avatar}
                class="h-12 w-12"
            />
            <Heading
                variant="small"
                title={review.presentation_name}
                description="{review.reviewer_name}'s review of your rehearsal from {when}"
            />
        </div>
        <span
            class="rounded-full px-3 py-1 text-xs font-semibold {completed
                ? 'bg-emerald-500/15 text-emerald-500'
                : 'bg-amber-500/15 text-amber-500'}"
            data-test="review-status"
        >
            {completed ? 'Completed' : 'Pending'}
        </span>
    </div>

    <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr]">
        <!-- Frozen deck, driven by the recording when one exists -->
        <section class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-sm font-semibold text-foreground">
                    Rehearsed deck
                </h2>
                <span class="text-xs text-muted-foreground">
                    Slide {currentSlide + 1} / {slideCount}{slideTitle(
                        currentSlide,
                    )
                        ? ` · ${slideTitle(currentSlide)}`
                        : ''}
                </span>
            </div>
            <!-- Keyed so hopping between sibling reviews remounts the deck
                 and re-reports the slide position. -->
            {#key review.id}
                <RehearsalReplay
                    content={review.content}
                    flow={review.flow}
                    stepEvents={review.step_events}
                    {audioUrl}
                    onSlideChange={(current, total) => {
                        currentSlide = current;
                        slideCount = total;
                    }}
                />
            {/key}
        </section>

        <!-- Comments, keyed to the shown slide -->
        <section class="flex flex-col gap-3">
            <div class="flex items-baseline gap-2">
                <h2 class="text-sm font-semibold text-foreground">
                    Comments for slide {currentSlide + 1}
                </h2>
                {#if slideTitle(currentSlide)}
                    <span class="truncate text-xs text-muted-foreground">
                        {slideTitle(currentSlide)}
                    </span>
                {/if}
            </div>

            {#if currentSlideComments.length > 0}
                <ul class="flex flex-col gap-2">
                    {#each currentSlideComments as comment (comment.id)}
                        <li
                            class="rounded-lg border border-amber-500/60 bg-amber-500/10 p-2.5 text-sm"
                            data-test="review-comment"
                        >
                            <p class="text-foreground">{comment.message}</p>
                        </li>
                    {/each}
                </ul>
            {/if}

            {#if otherComments.length > 0}
                <h3
                    class="mt-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Other slides
                </h3>
                <ul class="flex flex-col gap-2">
                    {#each otherComments as comment (comment.id)}
                        <li
                            class="rounded-lg border border-border bg-card p-2.5 text-sm"
                            data-test="review-comment"
                        >
                            <p
                                class="mb-1 flex items-center gap-1 text-xs font-semibold text-muted-foreground"
                            >
                                <MessageSquare class="h-3 w-3" />
                                Slide {comment.slide_number + 1}
                                {#if slideTitle(comment.slide_number)}
                                    <span class="truncate font-normal">
                                        · {slideTitle(comment.slide_number)}
                                    </span>
                                {/if}
                            </p>
                            <p class="text-foreground">{comment.message}</p>
                        </li>
                    {/each}
                </ul>
            {/if}

            {#if review.comments.length === 0}
                <p
                    class="rounded-xl border border-dashed border-border bg-card px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    {completed
                        ? 'The reviewer left no comments.'
                        : 'No comments yet. The reviewer has not finished this review.'}
                </p>
            {/if}

            {#if siblingReviews.length > 0}
                <h3
                    class="mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Other reviews on this rehearsal
                </h3>
                <ul class="flex flex-col gap-2">
                    {#each siblingReviews as sibling (sibling.id)}
                        <li
                            class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card p-2.5"
                            data-test="sibling-review"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <UserAvatar
                                    name={sibling.reviewer_name}
                                    avatar={sibling.reviewer_avatar}
                                    class="h-7 w-7 shrink-0"
                                />
                                <span class="truncate text-sm text-foreground">
                                    {sibling.reviewer_name}
                                </span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold {sibling.status ===
                                    'completed'
                                        ? 'bg-emerald-500/15 text-emerald-500'
                                        : 'bg-amber-500/15 text-amber-500'}"
                                >
                                    {sibling.status === 'completed'
                                        ? 'Completed'
                                        : 'Pending'}
                                </span>
                            </div>
                            <Button
                                variant="outline"
                                size="sm"
                                onclick={() => openReview(sibling.id)}
                            >
                                View
                            </Button>
                        </li>
                    {/each}
                </ul>
            {/if}

            {#if otherRuns.length > 0}
                <h3
                    class="mt-4 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    Reviews on other rehearsals of this talk
                </h3>
                <ul class="flex flex-col gap-2">
                    {#each otherRuns as run (run.practice_run_id)}
                        <li
                            class="flex flex-col gap-2 rounded-lg border border-border bg-card p-2.5"
                            data-test="other-run"
                        >
                            <p
                                class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                            >
                                <Timer class="h-3.5 w-3.5" />
                                Rehearsed {formatDate(run.rehearsed_at)}
                            </p>
                            <ul class="flex flex-col gap-1.5">
                                {#each run.reviews as other (other.id)}
                                    <li
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div
                                            class="flex min-w-0 items-center gap-2"
                                        >
                                            <UserAvatar
                                                name={other.reviewer_name}
                                                avatar={other.reviewer_avatar}
                                                class="h-6 w-6 shrink-0"
                                            />
                                            <span
                                                class="truncate text-sm text-foreground"
                                            >
                                                {other.reviewer_name}
                                            </span>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onclick={() => openReview(other.id)}
                                        >
                                            View
                                        </Button>
                                    </li>
                                {/each}
                            </ul>
                        </li>
                    {/each}
                </ul>
            {/if}
        </section>
    </div>
</div>
