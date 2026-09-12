<script module lang="ts">
    import { dashboard, users as usersRoute } from '@/routes/admin';

    export const layout = (props: { user: { name: string } }) => ({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: usersRoute() },
            { title: props.user.name, href: '#' },
        ],
    });
</script>

<script lang="ts">
    import Radio from 'lucide-svelte/icons/radio';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';

    type Engagement = {
        total_sessions: number;
        total_reactions: number;
        total_viewers: number;
        avg_reactions_per_session: number;
        top_emoji: string | null;
    };

    type SessionRow = {
        id: number;
        presentation_name: string;
        started_at: string;
        duration_seconds: number;
        is_live: boolean;
        viewer_count: number;
        reaction_total: number;
        reaction_counts: Record<string, number>;
    };

    type TeamAnalytics = {
        name: string;
        slug: string;
        is_personal: boolean;
        engagement: Engagement;
        recentSessions: SessionRow[];
    };

    type UserInfo = {
        id: number;
        name: string;
        email: string;
        avatar: string;
        created_at: string | null;
    };

    let {
        user,
        teams = [],
    }: { user: UserInfo; teams?: TeamAnalytics[] } = $props();

    const numberFormatter = new Intl.NumberFormat();

    const statsFor = (engagement: Engagement) => [
        { label: 'Talks given', value: engagement.total_sessions },
        { label: 'People reached', value: engagement.total_viewers },
        { label: 'Reactions', value: engagement.total_reactions },
        { label: 'Avg per talk', value: engagement.avg_reactions_per_session },
    ];

    const formatDuration = (seconds: number): string => {
        if (seconds < 60) {
            return `${seconds}s`;
        }
        const m = Math.floor(seconds / 60);
        const h = Math.floor(m / 60);
        return h > 0 ? `${h}h ${m % 60}m` : `${m}m`;
    };

    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });

    const formatDate = (value: string): string =>
        dateFormatter.format(new Date(value));

    const sortedReactions = (counts: Record<string, number>) =>
        Object.entries(counts).sort(([, a], [, b]) => b - a);
</script>

<AppHead title={user.name} />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex items-center gap-4">
        <img
            src={user.avatar}
            alt=""
            class="h-14 w-14 shrink-0 rounded-full object-cover"
        />
        <Heading variant="small" title={user.name} description={user.email} />
    </div>

    {#if teams.length === 0}
        <p
            class="rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center text-sm text-muted-foreground"
        >
            This user isn't on any team yet.
        </p>
    {/if}

    {#each teams as team (team.slug)}
        <section class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-foreground">
                    {team.name}
                </h2>
                {#if team.is_personal}
                    <span
                        class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        Personal
                    </span>
                {/if}
            </div>

            <div
                class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3 lg:grid-cols-5"
            >
                {#each statsFor(team.engagement) as stat (stat.label)}
                    <div class="bg-card p-5">
                        <p
                            class="font-mono text-3xl font-bold tabular-nums text-foreground"
                        >
                            {numberFormatter.format(stat.value)}
                        </p>
                        <p
                            class="mt-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                        >
                            {stat.label}
                        </p>
                    </div>
                {/each}
                <div class="flex flex-col justify-between bg-card p-5">
                    <span class="text-3xl leading-none" aria-hidden="true">
                        {team.engagement.top_emoji ?? '—'}
                    </span>
                    <p
                        class="mt-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        Loudest reaction
                    </p>
                </div>
            </div>

            {#if team.recentSessions.length > 0}
                <ul class="flex flex-col gap-2">
                    {#each team.recentSessions as session (session.id)}
                        <li class="rounded-xl border border-border bg-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-display font-semibold text-foreground"
                                    >
                                        {session.presentation_name}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {formatDate(session.started_at)} · {formatDuration(
                                            session.duration_seconds,
                                        )} · {session.viewer_count}
                                        {session.viewer_count === 1
                                            ? 'viewer'
                                            : 'viewers'}
                                    </p>
                                </div>
                                {#if session.is_live}
                                    <span
                                        class="flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-500"
                                    >
                                        <Radio class="h-3 w-3" /> Live
                                    </span>
                                {:else}
                                    <span
                                        class="shrink-0 font-mono text-sm tabular-nums text-muted-foreground"
                                    >
                                        {session.reaction_total} ⚡
                                    </span>
                                {/if}
                            </div>

                            {#if session.reaction_total > 0}
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    {#each sortedReactions(session.reaction_counts) as [emoji, count] (emoji)}
                                        <span
                                            class="flex items-center gap-1 rounded-md bg-accent px-2 py-0.5 text-sm"
                                        >
                                            <span aria-hidden="true">{emoji}</span>
                                            <span
                                                class="font-mono text-xs tabular-nums text-muted-foreground"
                                                >{count}</span
                                            >
                                        </span>
                                    {/each}
                                </div>
                            {/if}
                        </li>
                    {/each}
                </ul>
            {:else}
                <p
                    class="rounded-xl border border-dashed border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No talks in this team yet.
                </p>
            {/if}
        </section>
    {/each}
</div>
