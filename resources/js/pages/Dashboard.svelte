<script module lang="ts">
    import { dashboard } from '@/routes';
    import type { Team } from '@/types';

    export const layout = (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    });
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Mic from 'lucide-svelte/icons/mic';
    import Presentation from 'lucide-svelte/icons/presentation';
    import Radio from 'lucide-svelte/icons/radio';
    import Trash2 from 'lucide-svelte/icons/trash-2';
    import AppHead from '@/components/AppHead.svelte';
    import Confirm from '@/components/feedback/Confirm.svelte';
    import Heading from '@/components/Heading.svelte';
    import PendingInvitationsModal from '@/components/PendingInvitationsModal.svelte';
    import { Button } from '@/components/ui/button';
    import { edit, index, present } from '@/routes/presentations';
    import { show as showRehearsal } from '@/routes/rehearsals';
    import {
        destroy as destroySession,
        show as showSession,
    } from '@/routes/sessions';
    import type { DashboardInvitation } from '@/types';
    import type { DeliveryStats } from '@/types/generated';

    type Engagement = {
        total_sessions: number;
        total_reactions: number;
        total_viewers: number;
        avg_reactions_per_session: number;
        top_emoji: string | null;
    };

    type TimelineItem = {
        type: 'session' | 'rehearsal';
        id: number;
        presentation_id: number;
        presentation_name: string;
        started_at: string;
        duration_seconds: number;
        is_live: boolean;
        viewer_count: number;
        reaction_total: number;
        reaction_counts: Record<string, number>;
    };

    type DeckRow = {
        id: number;
        name: string;
        slide_count: number;
        updated_at: string | null;
    };

    let {
        pendingInvitations = [],
        engagement,
        speakingStats,
        timeline = [],
        recentDecks = [],
    }: {
        pendingInvitations?: DashboardInvitation[];
        engagement: Engagement;
        speakingStats: DeliveryStats;
        timeline?: TimelineItem[];
        recentDecks?: DeckRow[];
    } = $props();

    const teamSlug = $derived(page.props.currentTeam?.slug ?? '');

    const stats = $derived([
        { label: 'Talks given', value: engagement.total_sessions },
        { label: 'People reached', value: engagement.total_viewers },
        { label: 'Reactions', value: engagement.total_reactions },
        { label: 'Avg per talk', value: engagement.avg_reactions_per_session },
    ]);

    const numberFormatter = new Intl.NumberFormat();

    const formatDuration = (seconds: number): string => {
        if (seconds < 60) {
            return `${seconds}s`;
        }

        const m = Math.floor(seconds / 60);
        const h = Math.floor(m / 60);

        return h > 0 ? `${h}h ${m % 60}m` : `${m}m`;
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

    // Reaction chips, biggest tally first.
    const sortedReactions = (counts: Record<string, number>) =>
        Object.entries(counts).sort(([, a], [, b]) => b - a);

    const openDeck = (id: number) =>
        router.visit(edit({ current_team: teamSlug, presentation: id }).url);

    const presentDeck = (id: number) =>
        router.visit(present({ current_team: teamSlug, presentation: id }).url);

    const openItem = (item: TimelineItem) =>
        router.visit(
            item.type === 'session'
                ? showSession({ current_team: teamSlug, session: item.id }).url
                : showRehearsal({ current_team: teamSlug, rehearsal: item.id })
                      .url,
        );

    // Escape hatch for a session that went live by mistake (instead of a test
    // run). Deleting drops its reactions/viewers from every stat, so it asks.
    let confirmModal: Confirm;

    const deleteSession = (item: TimelineItem) =>
        confirmModal.confirm({
            title: `Delete this session of “${item.presentation_name}”?`,
            text: 'Its viewers, reactions and speaking time disappear from your stats. This cannot be undone.',
            variant: 'destructive',
            action: 'Delete session',
            onConfirm: () =>
                router.delete(
                    destroySession({
                        current_team: teamSlug,
                        session: item.id,
                    }).url,
                    { preserveScroll: true },
                ),
        });

    // "Know yourself": what your own runs (rehearsals + live talks) measure
    // about how you deliver, the numbers to hold against the linter's estimates.
    const measuredRuns = $derived(
        speakingStats.rehearsalCount + speakingStats.sessionCount,
    );

    const speakingCards = $derived(
        [
            {
                label: 'Time on stage',
                value: formatDuration(speakingStats.totalSpokenSeconds),
            },
            {
                label: 'Runs',
                value: `${measuredRuns}`,
                detail: `${speakingStats.rehearsalCount} rehearsed · ${speakingStats.sessionCount} live`,
            },
            speakingStats.avgRunSeconds
                ? {
                      label: 'Average run',
                      value: formatDuration(speakingStats.avgRunSeconds),
                  }
                : null,
            speakingStats.avgSecondsPerSlide
                ? {
                      label: 'Average per slide',
                      value: formatDuration(speakingStats.avgSecondsPerSlide),
                  }
                : null,
            speakingStats.avgWordsPerMinute
                ? {
                      label: 'Words / min',
                      value: `${speakingStats.avgWordsPerMinute}`,
                  }
                : {
                      label: 'Words / min',
                      value: `N/A`,
                  },
        ].filter((card) => card !== null),
    );

    let statsTab: 'room' | 'self' = $state('room');

    // The timeline's two lanes: live talks left, rehearsals right. Every row
    // renders both cells so each lane's line stays continuous.
    const lanes = ['session', 'rehearsal'] as const;
</script>

<AppHead title="Dashboard" />

{#if pendingInvitations.length > 0}
    <PendingInvitationsModal invitations={pendingInvitations} />
{/if}

<Confirm bind:this={confirmModal} />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <Heading
            variant="small"
            title="Dashboard"
            description="How your talks landed with the room"
        />
        <div class="flex items-center gap-1.5">
            <!-- Quick access to the two most recently edited decks. -->
            {#each recentDecks as deck (deck.id)}
                <Button
                    variant="ghost"
                    size="sm"
                    class="max-w-44 text-muted-foreground"
                    title={`Edit “${deck.name}”`}
                    onclick={() => openDeck(deck.id)}
                >
                    <Presentation class="h-3.5 w-3.5 shrink-0" />
                    <span class="truncate">{deck.name}</span>
                </Button>
            {/each}
            <Button
                variant="outline"
                onclick={() => router.visit(index(teamSlug).url)}
            >
                All presentations
            </Button>
        </div>
    </div>

    <!-- Stats, tabbed: the room's verdict on one side, your own delivery
         habits on the other. -->
    <section class="flex flex-col gap-3">
        <div
            class="flex items-center gap-4 border-b border-border"
            role="tablist"
        >
            <button
                type="button"
                role="tab"
                aria-selected={statsTab === 'room'}
                class="-mb-px border-b-2 pb-2 text-sm font-semibold transition-colors {statsTab ===
                'room'
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground'}"
                onclick={() => (statsTab = 'room')}
            >
                How the talks landed
            </button>
            <button
                type="button"
                role="tab"
                aria-selected={statsTab === 'self'}
                class="-mb-px border-b-2 pb-2 text-sm font-semibold transition-colors {statsTab ===
                'self'
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:text-foreground'}"
                onclick={() => (statsTab = 'self')}
            >
                Know yourself
            </button>
        </div>

        {#if statsTab === 'room'}
            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3 lg:grid-cols-5"
            >
                {#each stats as stat (stat.label)}
                    <div class="bg-card p-3">
                        <p
                            class="font-mono text-xl font-bold tabular-nums text-foreground"
                        >
                            {numberFormatter.format(stat.value)}
                        </p>
                        <p
                            class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                        >
                            {stat.label}
                        </p>
                    </div>
                {/each}

                <div
                    class="flex flex-col justify-between bg-card p-3"
                    data-test="loudest-reaction"
                >
                    <span class="text-xl leading-none" aria-hidden="true">
                        {engagement.top_emoji ?? '—'}
                    </span>
                    <p
                        class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        Loudest reaction
                    </p>
                </div>
            </div>
        {:else if measuredRuns > 0}
            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3 lg:grid-cols-5"
                data-test="know-yourself"
            >
                {#each speakingCards as card (card.label)}
                    <div class="bg-card p-3">
                        <p
                            class="font-mono text-xl font-bold tabular-nums text-foreground"
                        >
                            {card.value}
                        </p>
                        <p
                            class="mt-0.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                        >
                            {card.label}
                        </p>
                        {#if card.detail}
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {card.detail}
                            </p>
                        {/if}
                    </div>
                {/each}
            </div>
        {:else}
            <p
                class="rounded-xl border border-dashed border-border bg-card px-4 py-6 text-center text-sm text-muted-foreground"
            >
                Rehearse or present a deck and your delivery stats appear here.
            </p>
        {/if}
    </section>

    <!-- The timeline: live talks and rehearsals on two parallel lanes,
         interleaved chronologically. -->
    <section class="flex flex-col gap-3">
        <h2 class="text-sm font-semibold text-foreground">Timeline</h2>

        {#if timeline.length > 0}
            <div>
                <div class="grid grid-cols-2 gap-x-6 pb-3">
                    <p
                        class="flex items-center gap-1.5 pl-8 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        <Radio class="h-3 w-3" /> Talks
                    </p>
                    <p
                        class="flex items-center gap-1.5 pl-8 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        <Mic class="h-3 w-3" /> Rehearsals
                    </p>
                </div>

                <ol>
                    {#each timeline as item, i (`${item.type}-${item.id}`)}
                        <li class="grid grid-cols-2 gap-x-6">
                            {#each lanes as lane (lane)}
                                <div
                                    class="relative pl-8 {i ===
                                    timeline.length - 1
                                        ? ''
                                        : 'pb-4'}"
                                >
                                    <!-- Lane line segment; segments stack into a
                                         continuous vertical line. -->
                                    <span
                                        class="absolute bottom-0 left-2 top-0 w-px bg-border"
                                        aria-hidden="true"
                                    ></span>

                                    {#if item.type === lane}
                                        <span
                                            class="absolute left-[4.5px] top-2 h-2 w-2 rounded-full {item.is_live
                                                ? 'animate-pulse bg-emerald-500'
                                                : lane === 'session'
                                                  ? 'bg-primary'
                                                  : 'border border-muted-foreground bg-card'}"
                                            aria-hidden="true"
                                        ></span>

                                        <div
                                            class="rounded-xl border border-border bg-card p-3"
                                            data-test={lane === 'session'
                                                ? 'session-row'
                                                : 'rehearsal-row'}
                                        >
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <!-- svelte-ignore a11y_no_static_element_interactions, a11y_click_events_have_key_events -->
                                                <div
                                                    class="min-w-0 cursor-pointer"
                                                    onclick={() =>
                                                        openItem(item)}
                                                >
                                                    <p
                                                        class="truncate font-display text-sm font-semibold text-foreground hover:underline"
                                                    >
                                                        {item.presentation_name}
                                                    </p>
                                                    <p
                                                        class="mt-0.5 text-xs text-muted-foreground"
                                                    >
                                                        {formatWhen(
                                                            item.started_at,
                                                        )} · {formatDuration(
                                                            item.duration_seconds,
                                                        )}{#if lane === 'session'}
                                                            · {item.viewer_count}
                                                            {item.viewer_count ===
                                                            1
                                                                ? 'viewer'
                                                                : 'viewers'}
                                                        {/if}
                                                    </p>
                                                </div>

                                                <div
                                                    class="flex shrink-0 items-center gap-1"
                                                >
                                                    {#if item.is_live}
                                                        <span
                                                            class="flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-500"
                                                        >
                                                            <Radio
                                                                class="h-3 w-3"
                                                            /> Live
                                                        </span>
                                                    {:else if lane === 'session'}
                                                        <!-- Total reactions; the
                                                             per-emoji detail pops
                                                             on hover. -->
                                                        <span
                                                            class="group relative font-mono text-sm tabular-nums text-muted-foreground"
                                                        >
                                                            {item.reaction_total}
                                                            ⚡
                                                            {#if item.reaction_total > 0}
                                                                <span
                                                                    class="pointer-events-none absolute right-0 top-full z-10 mt-1 hidden max-w-64 flex-wrap justify-end gap-1.5 rounded-lg border border-border bg-popover p-2 shadow-md group-hover:flex"
                                                                    data-test="reaction-detail"
                                                                >
                                                                    {#each sortedReactions(item.reaction_counts) as [emoji, count] (emoji)}
                                                                        <span
                                                                            class="flex items-center gap-1 rounded-md bg-accent px-2 py-0.5 text-sm"
                                                                        >
                                                                            <span
                                                                                aria-hidden="true"
                                                                                >{emoji}</span
                                                                            >
                                                                            <span
                                                                                class="font-mono text-xs tabular-nums text-muted-foreground"
                                                                                >{count}</span
                                                                            >
                                                                        </span>
                                                                    {/each}
                                                                </span>
                                                            {/if}
                                                        </span>
                                                    {/if}
                                                    {#if lane === 'session'}
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-7 w-7 p-0 text-muted-foreground hover:text-destructive"
                                                            title="Delete this session and its stats"
                                                            aria-label="Delete session"
                                                            onclick={() =>
                                                                deleteSession(
                                                                    item,
                                                                )}
                                                            data-test="dashboard-delete-session"
                                                        >
                                                            <Trash2
                                                                class="h-3.5 w-3.5"
                                                            />
                                                        </Button>
                                                    {/if}
                                                </div>
                                            </div>
                                        </div>
                                    {/if}
                                </div>
                            {/each}
                        </li>
                    {/each}
                </ol>
            </div>
        {:else}
            <div
                class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center"
            >
                <Radio class="h-6 w-6 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    No talks or rehearsals yet. Present a deck and the room's
                    reactions land here.
                </p>
                {#if recentDecks.length > 0}
                    <Button onclick={() => presentDeck(recentDecks[0].id)}>
                        Present “{recentDecks[0].name}”
                    </Button>
                {:else}
                    <Button
                        variant="outline"
                        onclick={() => router.visit(index(teamSlug).url)}
                    >
                        Create a deck
                    </Button>
                {/if}
            </div>
        {/if}
    </section>
</div>
