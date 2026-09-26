<script module lang="ts">
    import { dashboard, feedback } from '@/routes/admin';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Feedback', href: feedback() },
        ],
    });
</script>

<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';

    type FeedbackRow = {
        id: number;
        message: string;
        user_name: string | null;
        user_email: string | null;
        created_at: string | null;
    };

    let { feedback: rows = [] }: { feedback?: FeedbackRow[] } = $props();

    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    const formatDate = (value: string | null): string =>
        value ? dateFormatter.format(new Date(value)) : '—';
</script>

<AppHead title="Feedback" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Feedback"
        description="{rows.length} {rows.length === 1 ? 'message' : 'messages'}"
    />

    {#if rows.length === 0}
        <div
            class="rounded-xl border border-dashed border-border bg-card p-10 text-center text-sm text-muted-foreground"
        >
            No feedback yet.
        </div>
    {:else}
        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-border text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        <th class="px-4 py-3">From</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Sent</th>
                    </tr>
                </thead>
                <tbody>
                    {#each rows as row (row.id)}
                        <tr
                            class="border-b border-border align-top last:border-0"
                            data-test="feedback-row"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-foreground">
                                    {row.user_name ?? 'Unknown'}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {row.user_email ?? '—'}
                                </p>
                            </td>
                            <td
                                class="max-w-xl px-4 py-3 whitespace-pre-wrap text-muted-foreground"
                            >
                                {row.message}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {formatDate(row.created_at)}
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}
</div>
