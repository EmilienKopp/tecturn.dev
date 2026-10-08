<script lang="ts">
    import { Avatar } from 'daisy-svelte';
    import { getInitials } from '@/lib/initials';
    import type { Team, User } from '@/types';

    let {
        user,
        showEmail = false,
        team = null,
    }: {
        user: User;
        showEmail?: boolean;
        team?: Team | null;
    } = $props();

    const showAvatar = $derived(user.avatar && user.avatar !== '');
</script>

{#if user}
    <Avatar
        src={showAvatar ? user.avatar : null}
        alt={user.name}
        fallback={getInitials(user.name)}
        class="h-8 w-8 rounded-lg"
    />

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{user.name}</span>
        {#if team}
            <span class="truncate text-xs text-muted-foreground"
                >{team.name}</span
            >
        {:else if showEmail}
            <span class="truncate text-xs text-muted-foreground"
                >{user.email}</span
            >
        {/if}
    </div>
{/if}
