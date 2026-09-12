<script module lang="ts">
    import { dashboard, users as usersRoute } from '@/routes/admin';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Users', href: usersRoute() },
        ],
    });
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { show as showUser } from '@/routes/admin/users';

    type UserRow = {
        id: number;
        name: string;
        email: string;
        avatar: string;
        team_count: number;
        current_team_name: string | null;
        created_at: string | null;
    };

    let { users = [] }: { users?: UserRow[] } = $props();

    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    const formatDate = (value: string | null): string =>
        value ? dateFormatter.format(new Date(value)) : '—';
</script>

<AppHead title="Users" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <Heading
        variant="small"
        title="Users"
        description="{users.length} registered {users.length === 1
            ? 'account'
            : 'accounts'}"
    />

    <div class="overflow-hidden rounded-xl border border-border bg-card">
        <table class="w-full text-sm">
            <thead>
                <tr
                    class="border-b border-border text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Current team</th>
                    <th class="px-4 py-3 text-right">Teams</th>
                    <th class="px-4 py-3 text-right">Joined</th>
                </tr>
            </thead>
            <tbody>
                {#each users as user (user.id)}
                    <tr
                        class="cursor-pointer border-b border-border last:border-0 hover:bg-muted/50"
                        data-test="user-row"
                        onclick={() =>
                            router.visit(showUser(user.id).url)}
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img
                                    src={user.avatar}
                                    alt=""
                                    class="h-8 w-8 shrink-0 rounded-full object-cover"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="truncate font-medium text-foreground"
                                    >
                                        {user.name}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {user.email}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {user.current_team_name ?? '—'}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono tabular-nums text-muted-foreground"
                        >
                            {user.team_count}
                        </td>
                        <td
                            class="px-4 py-3 text-right text-muted-foreground"
                        >
                            {formatDate(user.created_at)}
                        </td>
                    </tr>
                {/each}
            </tbody>
        </table>
    </div>
</div>
