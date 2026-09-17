<script module lang="ts">
    import { index } from '@/routes/rehearsals';
    import type { Team } from '@/types';

    export const layout = (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Rehearsals',
                href: props.currentTeam ? index(props.currentTeam.slug) : '/',
            },
        ],
    });
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Timer from 'lucide-svelte/icons/timer';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { index as presentationsIndex } from '@/routes/presentations';
    import { show } from '@/routes/rehearsals';
    import { show as showReview } from '@/routes/reviews';

    type RunRow = {
        id: number;
        presentation_id: number;
        presentation_name: string;
        started_at: string;
        duration_seconds: number;
        slide_count: number;
        slide_timings: { slide: number; seconds: number }[];
    };

    type ReviewRequestRow = {
        id: number;
        practice_run_id: number;
        presentation_name: string;
        requester_name: string;
        requester_avatar: string | null;
        status: string;
        rehearsed_at: string;
        duration_seconds: number;
        requested_at: string | null;
    };

    let {
        runs = [],
        reviewRequests = [],
    }: {
        runs?: RunRow[];
        reviewRequests?: ReviewRequestRow[];
    } = $props();

    const teamSlug = $derived(page.props.currentTeam?.slug ?? '');

    const formatDuration = (seconds: number): string => {
        if (seconds < 60) {
            return `${seconds}s`;
        }

        const m = Math.floor(seconds / 60);
        const h = Math.floor(m / 60);

        return h > 0 ? `${h}h ${m % 60}m` : `${m}m ${seconds % 60}s`;
    };

    const formatWhen = (value: string): string => {
        const then = new Date(value).getTime();
        const diff = Date.now() - then;
        const minutes = Math.round(diff / 60000);

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

    const openRun = (id: number) =>
        router.visit(show({ current_team: teamSlug, practice_run: id }).url);
</script>

<AppHead title="Rehearsals" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Rehearsals"
        description="Your practice runs, each saved with the deck as it was that day"
    />

    {#if reviewRequests.length > 0}
        <section class="flex flex-col gap-3">
            <h2 class="text-sm font-semibold text-foreground">
                Reviews requested of me
            </h2>
            <ul class="flex flex-col gap-2">
                {#each reviewRequests as request (request.id)}
                    <li
                        class="rounded-xl border border-border bg-card p-4"
                        data-test="review-request-row"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <UserAvatar
                                    name={request.requester_name}
                                    avatar={request.requester_avatar}
                                    class="h-9 w-9 shrink-0"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-display font-semibold text-foreground"
                                    >
                                        {request.presentation_name}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {request.requester_name} · rehearsed {formatWhen(
                                            request.rehearsed_at,
                                        )}
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold {request.status ===
                                    'completed'
                                        ? 'bg-emerald-500/15 text-emerald-500'
                                        : 'bg-amber-500/15 text-amber-500'}"
                                >
                                    {request.status === 'completed'
                                        ? 'Completed'
                                        : 'Pending'}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onclick={() =>
                                        router.visit(
                                            showReview(request.id).url,
                                        )}
                                >
                                    Open review
                                </Button>
                            </div>
                        </div>
                    </li>
                {/each}
            </ul>
        </section>
    {/if}

    {#if runs.length > 0}
        <ul class="flex flex-col gap-2">
            {#each runs as run (run.id)}
                <li
                    class="rounded-xl border border-border bg-card p-4"
                    data-test="rehearsal-row"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p
                                class="truncate font-display font-semibold text-foreground"
                            >
                                {run.presentation_name}
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {formatWhen(run.started_at)} · {run.slide_count}
                                {run.slide_count === 1 ? 'slide' : 'slides'}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span
                                class="font-mono text-lg font-bold tabular-nums text-foreground"
                            >
                                {formatDuration(run.duration_seconds)}
                            </span>
                            <Button
                                variant="outline"
                                size="sm"
                                onclick={() => openRun(run.id)}
                            >
                                View
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
            <Timer class="h-6 w-6 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                No rehearsals yet. Open a deck and pick "Practice" from the
                Present menu to time a run-through.
            </p>
            <Button
                variant="outline"
                onclick={() => router.visit(presentationsIndex(teamSlug).url)}
            >
                Your presentations
            </Button>
        </div>
    {/if}
</div>
