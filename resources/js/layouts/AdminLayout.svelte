<script lang="ts">
    import type { Snippet } from 'svelte';
    import AdminLayout from '@/layouts/admin/AdminSidebarLayout.svelte';
    import type { BreadcrumbItem } from '@/types';

    let {
        breadcrumbs = [],
        children,
    }: {
        breadcrumbs?: BreadcrumbItem[];
        children?: Snippet;
    } = $props();
</script>

<AdminLayout {breadcrumbs}>
    {@render children?.()}
</AdminLayout>
