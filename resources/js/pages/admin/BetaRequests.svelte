<script module lang="ts">
    import { betaRequests, dashboard } from '@/routes/admin';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Requests', href: betaRequests() },
        ],
    });
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { approve, reject } from '@/routes/admin/beta-requests';

    type BetaRequestRow = {
        id: number;
        name: string;
        email: string;
        message: string | null;
        status: 'pending' | 'approved' | 'rejected';
        created_at: string | null;
    };

    let { requests = [] }: { requests?: BetaRequestRow[] } = $props();

    const pendingCount = $derived(
        requests.filter((request) => request.status === 'pending').length,
    );

    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    const formatDate = (value: string | null): string =>
        value ? dateFormatter.format(new Date(value)) : '—';

    const statusStyles: Record<BetaRequestRow['status'], string> = {
        pending: 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
        approved: 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
        rejected: 'bg-rose-500/15 text-rose-600 dark:text-rose-400',
    };

    // Ids with a request in flight, so we can disable both buttons on that row.
    let processing = $state<number[]>([]);

    const decide = (id: number, action: typeof approve | typeof reject) => {
        processing = [...processing, id];

        router.post(
            action(id).url,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    processing = processing.filter((pending) => pending !== id);
                },
            },
        );
    };
</script>

<AppHead title="Requests" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Beta requests"
        description="{pendingCount} pending {pendingCount === 1
            ? 'request'
            : 'requests'}"
    />

    {#if requests.length === 0}
        <div
            class="rounded-xl border border-dashed border-border bg-card p-10 text-center text-sm text-muted-foreground"
        >
            No beta requests yet.
        </div>
    {:else}
        <div class="overflow-hidden rounded-xl border border-border bg-card">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-border text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                    >
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Message</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {#each requests as request (request.id)}
                        <tr
                            class="border-b border-border align-top last:border-0"
                            data-test="beta-request-row"
                        >
                            <td class="px-4 py-3">
                                <p class="font-medium text-foreground">
                                    {request.name}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {request.email}
                                </p>
                            </td>
                            <td
                                class="max-w-sm px-4 py-3 text-muted-foreground"
                            >
                                {request.message ?? '—'}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {formatDate(request.created_at)}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold capitalize {statusStyles[
                                        request.status
                                    ]}"
                                >
                                    {request.status}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="default"
                                        disabled={request.status ===
                                            'approved' ||
                                            processing.includes(request.id)}
                                        onclick={() =>
                                            decide(request.id, approve)}
                                        data-test="approve-request"
                                    >
                                        Approve
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={request.status ===
                                            'rejected' ||
                                            processing.includes(request.id)}
                                        onclick={() =>
                                            decide(request.id, reject)}
                                        data-test="reject-request"
                                    >
                                        Reject
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    {/each}
                </tbody>
            </table>
        </div>
    {/if}
</div>
