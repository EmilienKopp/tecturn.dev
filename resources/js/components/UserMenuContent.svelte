<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import LogOut from 'lucide-svelte/icons/log-out';
    import Settings from 'lucide-svelte/icons/settings';
    import UserInfo from '@/components/UserInfo.svelte';
    import { toUrl } from '@/lib/utils';
    import { logout } from '@/routes';
    import { edit } from '@/routes/profile';
    import type { User } from '@/types';

    let {
        user,
        close,
    }: {
        user: User;
        close?: () => void;
    } = $props();

    function handleLogout() {
        close?.();
        router.flushAll();
    }
</script>

<li class="menu-title p-0 font-normal">
    <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
        <UserInfo {user} showEmail={true} />
    </div>
</li>
<li class="border-base-300 my-1 border-t"></li>
<li>
    <Link href={toUrl(edit())} prefetch onclick={() => close?.()}>
        <Settings class="mr-2 h-4 w-4" />
        Settings
    </Link>
</li>
<li class="border-base-300 my-1 border-t"></li>
<li>
    <Link
        href={logout()}
        as="button"
        onclick={handleLogout}
        data-test="logout-button"
    >
        <LogOut class="mr-2 h-4 w-4" />
        Log out
    </Link>
</li>
