<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
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
    import { dashboard, users } from '@/routes/admin';
    import type { NavItem } from '@/types';

    const items: NavItem[] = [
        { title: 'Overview', href: dashboard(), icon: LayoutGrid },
        { title: 'Users', href: users(), icon: Users },
    ];
</script>

<Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton size="lg" asChild>
                    {#snippet children(props)}
                        <Link {...props} href={dashboard().url} class={props.class}>
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
