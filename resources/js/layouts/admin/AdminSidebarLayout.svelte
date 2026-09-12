<script lang="ts">
    import type { Snippet } from 'svelte';
    import AdminSidebar from '@/components/AdminSidebar.svelte';
    import AppContent from '@/components/AppContent.svelte';
    import AppShell from '@/components/AppShell.svelte';
    import AppSidebarHeader from '@/components/AppSidebarHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AppShell variant="sidebar">
    <AdminSidebar />
    <AppContent variant="sidebar" class="overflow-x-hidden">
        <AppSidebarHeader {breadcrumbs} />
        {@render children?.()}
    </AppContent>
    <Toaster />
</AppShell>
