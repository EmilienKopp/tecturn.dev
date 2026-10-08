<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Breadcrumbs } from 'daisy-svelte';
    import { toUrl } from '@/lib/utils';
    import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs: BreadcrumbItemType[];
    } = $props();

    const items = $derived(
        breadcrumbs.map((item) => ({
            label: item.title,
            href: toUrl(item.href),
        })),
    );
</script>

<Breadcrumbs
    {items}
    onNavigate={(event, item) => {
        event.preventDefault();
        if (item.href) {
            router.visit(item.href);
        }
    }}
/>
