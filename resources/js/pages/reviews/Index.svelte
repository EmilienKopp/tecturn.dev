<script module lang="ts">
    import { index } from '@/routes/reviews';

    export const layout = {
        breadcrumbs: [{ title: 'Reviews', href: index().url }],
    };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import MessageSquare from 'lucide-svelte/icons/message-square';
    import X from 'lucide-svelte/icons/x';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { received, show } from '@/routes/reviews';

    type ReviewRow = {
        id: number;
        practice_run_id: number;
        presentation_name: string;
        requester_name?: string;
        requester_avatar?: string | null;
        reviewer_name?: string;
        reviewer_avatar?: string | null;
        status: string;
        rehearsed_at: string;
        duration_seconds: number;
        requested_at: string | null;
    };

    let {
        received: receivedReviews = [],
        requested = [],
    }: {
        received?: ReviewRow[];
        requested?: ReviewRow[];
    } = $props();

    type Tab = 'received' | 'requested';
    type StatusFilter = 'all' | 'pending' | 'completed';

    // The rehearsal page links here scoped to one run via "?run={id}".
    let runFilter = $state<number | null>(
        (() => {
            const raw = new URLSearchParams(window.location.search).get('run');
            const id = raw === null ? NaN : Number(raw);

            return Number.isInteger(id) && id > 0 ? id : null;
        })(),
    );

    // Land on the tab that has something to show for the filtered run.
    let tab = $state<Tab>(
        runFilter !== null &&
            requested.some((review) => review.practice_run_id === runFilter)
            ? 'requested'
            : 'received',
    );
    let status = $state<StatusFilter>('all');

    const rows = $derived(
        (tab === 'received' ? receivedReviews : requested)
            .filter((review) => status === 'all' || review.status === status)
            .filter(
                (review) =>
                    runFilter === null || review.practice_run_id === runFilter,
            ),
    );

    const counterpartName = (review: ReviewRow): string =>
        (tab === 'received' ? review.requester_name : review.reviewer_name) ??
        '';
    const counterpartAvatar = (review: ReviewRow): string | null =>
        (tab === 'received'
            ? review.requester_avatar
            : review.reviewer_avatar) ?? null;

    const formatWhen = (value: string): string => {
        const then = new Date(value).getTime();
        const minutes = Math.round((Date.now() - then) / 60000);

        if (minutes < 1) {
            return 'just now';
        }

        if (minutes < 60) {
            return `${minutes}m ago`;
        }

        const hours = Math.round(minutes / 60);

        if (hours < 24) {
            return `${hours}h ago`;
        }

        const days = Math.round(hours / 24);

        if (days < 7) {
            return `${days}d ago`;
        }

        return new Date(value).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    };

    const openReview = (review: ReviewRow): void => {
        router.visit(
            tab === 'received' ? show(review.id).url : received(review.id).url,
        );
    };

    const clearRunFilter = (): void => {
        runFilter = null;
        window.history.replaceState({}, '', index().url);
    };

    const tabs: { key: Tab; label: string }[] = [
        { key: 'received', label: 'Requested from me' },
        { key: 'requested', label: 'Requested to others' },
    ];

    const statuses: { key: StatusFilter; label: string }[] = [
        { key: 'all', label: 'All' },
        { key: 'pending', label: 'Pending' },
        { key: 'completed', label: 'Completed' },
    ];
</script>

<AppHead title="Reviews" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6">
    <Heading
        variant="small"
        title="Reviews"
        description="Rehearsal feedback you were asked for, and feedback you asked of others"
    />

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div
            class="flex rounded-lg border border-border bg-card p-1"
            role="tablist"
        >
            {#each tabs as { key, label } (key)}
                <button
                    type="button"
                    role="tab"
                    aria-selected={tab === key}
                    class="rounded-md px-3 py-1.5 text-sm font-semibold transition-colors {tab ===
                    key
                        ? 'bg-primary/15 text-primary'
                        : 'text-muted-foreground hover:text-foreground'}"
                    data-test="reviews-tab-{key}"
                    onclick={() => (tab = key)}
                >
                    {label}
                </button>
            {/each}
        </div>

        <div class="flex items-center gap-1">
            {#each statuses as { key, label } (key)}
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-xs font-semibold transition-colors {status ===
                    key
                        ? 'bg-foreground/10 text-foreground'
                        : 'text-muted-foreground hover:text-foreground'}"
                    data-test="reviews-status-{key}"
                    onclick={() => (status = key)}
                >
                    {label}
                </button>
            {/each}
        </div>
    </div>

    {#if runFilter !== null}
        <div
            class="flex items-center gap-2 self-start rounded-full border border-border bg-card px-3 py-1 text-xs text-muted-foreground"
        >
            Showing reviews for one rehearsal
            <button
                type="button"
                class="text-foreground"
                aria-label="Show all reviews"
                onclick={clearRunFilter}
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>
    {/if}

    {#if rows.length > 0}
        <ul class="flex flex-col gap-2">
            {#each rows as review (review.id)}
                <li
                    class="rounded-xl border border-border bg-card p-4"
                    data-test="review-row"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <UserAvatar
                                name={counterpartName(review)}
                                avatar={counterpartAvatar(review)}
                                class="h-9 w-9 shrink-0"
                            />
                            <div class="min-w-0">
                                <p
                                    class="truncate font-display font-semibold text-foreground"
                                >
                                    {review.presentation_name}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {counterpartName(review)} · rehearsed {formatWhen(
                                        review.rehearsed_at,
                                    )}{review.requested_at
                                        ? ` · asked ${formatWhen(review.requested_at)}`
                                        : ''}
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold {review.status ===
                                'completed'
                                    ? 'bg-emerald-500/15 text-emerald-500'
                                    : 'bg-amber-500/15 text-amber-500'}"
                            >
                                {review.status === 'completed'
                                    ? 'Completed'
                                    : 'Pending'}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                onclick={() => openReview(review)}
                            >
                                Open
                            </Button>
                        </div>
                    </div>
                </li>
            {/each}
        </ul>
    {:else}
        <div
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center"
        >
            <MessageSquare class="h-6 w-6 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                {tab === 'received'
                    ? 'Nobody has asked you for a review yet.'
                    : 'You have not requested any reviews yet. Open a rehearsal and ask a follower for feedback.'}
            </p>
        </div>
    {/if}
</div>
