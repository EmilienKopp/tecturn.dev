<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ChevronsUpDown from 'lucide-svelte/icons/chevrons-up-down';
    import { Dropdown } from 'daisy-svelte';
    import {
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
        useSidebar,
    } from '@/components/ui/sidebar';
    import UserInfo from '@/components/UserInfo.svelte';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import type { Team } from '@/types';

    const user = $derived(page.props.auth.user);
    const currentTeam = $derived(page.props.currentTeam as Team | null);
    const { isMobile, state: sidebarState } = useSidebar();
</script>

<SidebarMenu>
    <SidebarMenuItem>
        <div class="w-full [&>details]:w-full [&_summary]:w-full">
            <Dropdown
                position={$sidebarState === 'collapsed' && !$isMobile
                    ? 'left'
                    : 'top'}
                align="end"
                class="w-full min-w-56 rounded-lg"
            >
                {#snippet trigger()}
                    <SidebarMenuButton asChild size="lg">
                        {#snippet children(props)}
                            <span
                                class={props.class}
                                data-test="sidebar-menu-button"
                            >
                                <UserInfo {user} team={currentTeam} />
                                <ChevronsUpDown class="ml-auto size-4" />
                            </span>
                        {/snippet}
                    </SidebarMenuButton>
                {/snippet}
                {#snippet children({ close })}
                    <UserMenuContent {user} {close} />
                {/snippet}
            </Dropdown>
        </div>
    </SidebarMenuItem>
</SidebarMenu>
