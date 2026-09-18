<script module lang="ts">
    export const layout = {
        breadcrumbs: [{ title: 'Review', href: '#' }],
    };
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import CheckCircle from 'lucide-svelte/icons/check-circle';
    import MessageSquare from 'lucide-svelte/icons/message-square';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import RehearsalReplay from '@/components/tecturn/RehearsalReplay.svelte';
    import { Button } from '@/components/ui/button';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { shownSlideTitles } from '@/lib/tecturn/flow-compiler';
    import { complete as completeRoute } from '@/routes/reviews';
    import { store as storeComment } from '@/routes/reviews/comments';
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
        status: string;
        presentation_name: string;
        requester_name: string;
        requester_avatar: string | null;
        rehearsed_at: string;
        duration_seconds: number;
        content: PresentationContent;
        flow: FlowGraph | null;
        step_events: { at_ms: number; slide: number; step: number }[];
        has_recording: boolean;
        comments: ReviewComment[];
    };

    let {
        review,
        audioUrl = null,
    }: {
        review: Review;
        audioUrl?: string | null;
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

    let message = $state('');
    let sending = $state(false);

    const submitComment = (): void => {
        if (message.trim() === '' || completed) {
            return;
        }

        sending = true;
        router.post(
            storeComment(review.id).url,
            { slide_number: currentSlide, message: message.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    message = '';
                },
                onFinish: () => {
                    sending = false;
                },
            },
        );
    };

    let completing = $state(false);

    const markComplete = (): void => {
        completing = true;
        router.post(
            completeRoute(review.id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    completing = false;
                },
            },
        );
    };

    const commentErrors = $derived(page.props.errors ?? {});
</script>

<AppHead title="Review · {review.presentation_name}" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-center gap-4">
            <UserAvatar
                name={review.requester_name}
                avatar={review.requester_avatar}
                class="h-12 w-12"
            />
            <Heading
                variant="small"
                title={review.presentation_name}
                description="{review.requester_name} asked you to review this rehearsal from {when}"
            />
        </div>
        <div class="flex items-center gap-3">
            <span
                class="rounded-full px-3 py-1 text-xs font-semibold {completed
                    ? 'bg-emerald-500/15 text-emerald-500'
                    : 'bg-amber-500/15 text-amber-500'}"
                data-test="review-status"
            >
                {completed ? 'Completed' : 'Pending'}
            </span>
            {#if !completed}
                <Button
                    onclick={markComplete}
                    disabled={completing}
                    data-test="review-complete"
                >
                    <CheckCircle class="h-4 w-4" />
                    {completing ? 'Finishing…' : 'Mark review complete'}
                </Button>
            {/if}
        </div>
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

            {#if !completed}
                <div
                    class="flex flex-col gap-2 rounded-xl border border-border bg-card p-3"
                >
                    <textarea
                        class="min-h-20 w-full resize-y rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground"
                        placeholder="Feedback on this slide…"
                        bind:value={message}
                        data-test="review-comment-input"
                    ></textarea>
                    <div class="flex items-center justify-between gap-2">
                        {#if commentErrors.message}
                            <p class="text-xs text-red-500">
                                {commentErrors.message}
                            </p>
                        {:else}
                            <span class="text-xs text-muted-foreground">
                                Attached to slide {currentSlide + 1}
                            </span>
                        {/if}
                        <Button
                            size="sm"
                            onclick={submitComment}
                            disabled={sending || message.trim() === ''}
                            data-test="review-comment-submit"
                        >
                            {sending ? 'Sending…' : 'Add comment'}
                        </Button>
                    </div>
                </div>
            {:else}
                <p
                    class="rounded-xl border border-dashed border-border bg-card px-4 py-4 text-center text-xs text-muted-foreground"
                >
                    This review is completed; comments are read-only.
                </p>
            {/if}

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
                    No comments yet. Step through the deck and leave feedback
                    per slide.
                </p>
            {/if}
        </section>
    </div>
</div>
