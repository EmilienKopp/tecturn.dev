<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import AppContent from '@/components/AppContent.svelte';
    import AppShell from '@/components/AppShell.svelte';
    import AppSidebar from '@/components/AppSidebar.svelte';
    import AppSidebarHeader from '@/components/AppSidebarHeader.svelte';
    import { Toaster } from '@/components/ui/sonner';
    import { subscribeDeckToasts } from '@/lib/deck-toasts';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();

    // Toast the user when a deferred deck build finishes, wherever they are in the app.
    $effect(() => {
        const userId = page.props.auth.user?.id;

        if (!userId) {
            return;
        }

        return subscribeDeckToasts(userId);
    });
</script>

<AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar" class="overflow-x-hidden">
        <AppSidebarHeader {breadcrumbs} />
        {@render children?.()}
    </AppContent>
    <Toaster />
</AppShell>
