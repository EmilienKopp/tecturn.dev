<script module lang="ts">
    import { dashboard } from '@/routes/admin';

    export const layout = () => ({
        breadcrumbs: [{ title: 'Admin', href: dashboard() }],
    });
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';

    type Overview = {
        total_users: number;
        total_teams: number;
        total_workspaces: number;
        total_presentations: number;
        total_sessions: number;
        live_sessions: number;
        total_reactions: number;
        total_viewers: number;
    };

    let { overview }: { overview: Overview } = $props();

    const numberFormatter = new Intl.NumberFormat();

    const stats = $derived([
        { label: 'Users', value: overview.total_users },
        { label: 'Teams', value: overview.total_teams },
        { label: 'Workspaces', value: overview.total_workspaces },
        { label: 'Presentations', value: overview.total_presentations },
        { label: 'Sessions', value: overview.total_sessions },
        { label: 'Live now', value: overview.live_sessions },
        { label: 'Reactions', value: overview.total_reactions },
        { label: 'Viewers reached', value: overview.total_viewers },
    ]);
</script>

<AppHead title="Admin" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Admin"
        description="Platform-wide overview"
    />

    <section
        class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3 lg:grid-cols-4"
    >
        {#each stats as stat (stat.label)}
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
    </section>
</div>
