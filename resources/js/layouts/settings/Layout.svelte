<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Heading from '@/components/Heading.svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { index as aiCredentials } from '@/routes/ai-credentials';
    import { edit as editAppearance } from '@/routes/appearance';
    import { edit as editBranding } from '@/routes/branding';
    import { edit as editProfile } from '@/routes/profile';
    import { index as teams } from '@/routes/teams';
    import type { NavItem } from '@/types';

    let {
        children,
    }: {
        children?: Snippet;
    } = $props();

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: editProfile(),
        },
        {
            title: 'Branding',
            href: editBranding(),
        },
        {
            title: 'AI models',
            href: aiCredentials(),
        },
        {
            title: 'Teams',
            href: teams(),
        },
        {
            title: 'Appearance',
            href: editAppearance(),
        },
    ];

    const url = currentUrlState();
</script>

<div class="px-4 py-6">
    <Heading
        title="Settings"
        description="Manage your profile and account settings"
    />

    <div class="flex flex-col lg:flex-row lg:space-x-12">
        <aside class="w-full max-w-xl lg:w-48">
            <nav
                class="flex flex-col space-y-1 space-x-0"
                aria-label="Settings"
            >
                {#each sidebarNavItems as item (toUrl(item.href))}
                    <Link
                        href={toUrl(item.href)}
                        class="btn btn-ghost w-full justify-start {url.isCurrentOrParentUrl(
                            item.href,
                            url.currentUrl,
                        )
                            ? 'bg-muted'
                            : ''}"
                    >
                        {item.title}
                    </Link>
                {/each}
            </nav>
        </aside>

        <div class="divider my-6 lg:hidden"></div>

        <div class="flex-1 md:max-w-2xl">
            <section class="max-w-xl space-y-12">
                {@render children?.()}
            </section>
        </div>
    </div>
</div>
