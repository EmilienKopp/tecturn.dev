<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import Inbox from 'lucide-svelte/icons/inbox';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Megaphone from 'lucide-svelte/icons/megaphone';
    import ToggleRight from 'lucide-svelte/icons/toggle-right';
    import Users from 'lucide-svelte/icons/users';
    import AppLogo from '@/components/AppLogo.svelte';
    import NavMain from '@/components/NavMain.svelte';
    import NavUser from '@/components/NavUser.svelte';
    import {
        Sidebar,
        SidebarContent,
        SidebarFooter,
        SidebarHeader,
        SidebarMenu,
        SidebarMenuButton,
        SidebarMenuItem,
    } from '@/components/ui/sidebar';
    import {
        betaRequests,
        dashboard,
        features,
        feedback,
        users,
    } from '@/routes/admin';
    import type { NavItem } from '@/types';

    const items: NavItem[] = [
        { title: 'Overview', href: dashboard(), icon: LayoutGrid },
        { title: 'Users', href: users(), icon: Users },
        { title: 'Requests', href: betaRequests(), icon: Inbox },
        { title: 'Features', href: features(), icon: ToggleRight },
        { title: 'Feedback', href: feedback(), icon: Megaphone },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link
                            {...props}
                            href={dashboard().url}
                            class={props.class}
                        >
                            <AppLogo />
                        </Link>
                    {/snippet}
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
        <NavMain label="Admin" {items} />
    </SidebarContent>

    <SidebarFooter>
        <NavUser />
    </SidebarFooter>
</Sidebar>
