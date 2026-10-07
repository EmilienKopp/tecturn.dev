<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import BookOpen from 'lucide-svelte/icons/book-open';
    import Folder from 'lucide-svelte/icons/folder';
    import LayoutGrid from 'lucide-svelte/icons/layout-grid';
    import Menu from 'lucide-svelte/icons/menu';
    import Search from 'lucide-svelte/icons/search';
    import AppLogo from '@/components/AppLogo.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import TeamSwitcher from '@/components/TeamSwitcher.svelte';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { Button, Dropdown, Tooltip } from 'daisy-svelte';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { currentUrlState } from '@/lib/currentUrl.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard } from '@/routes';
    import type { BreadcrumbItem, NavItem, Team } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const auth = $derived(page.props.auth);
    const currentTeam = $derived(page.props.currentTeam as Team | null);
    const dashboardUrl = $derived(
        currentTeam ? dashboard(currentTeam.slug) : '/',
    );

    const url = currentUrlState();

    let mobileMenuOpen = $state(false);

    const navLinkStyles =
        'inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 hover:bg-accent hover:text-accent-foreground';

    const activeItemStyles =
        'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

    const mainNavItems = $derived<NavItem[]>([
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
    ]);

    const rightNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/svelte-starter-kit',
            icon: Folder,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#svelte',
            icon: BookOpen,
        },
    ];
</script>

<div>
    <div class="border-b border-sidebar-border/80">
        <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
            <div class="lg:hidden">
                <Button
                    variant="ghost"
                    size="icon"
                    class="mr-2 h-9 w-9"
                    aria-expanded={mobileMenuOpen}
                    onclick={() => (mobileMenuOpen = true)}
                >
                    <Menu class="h-5 w-5" />
                </Button>
                {#if mobileMenuOpen}
                    <div class="fixed inset-0 z-50 lg:hidden">
                        <button
                            type="button"
                            class="absolute inset-0 bg-black/50"
                            aria-label="Close menu"
                            onclick={() => (mobileMenuOpen = false)}
                        ></button>
                        <div
                            class="bg-base-100 absolute inset-y-0 left-0 w-[300px] overflow-y-auto p-6 shadow-lg"
                        >
                            <h2 class="sr-only">Navigation menu</h2>
                            <div class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </div>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 pt-6 pb-10"
                            >
                                <nav class="-mx-3 space-y-1">
                                    {#each mainNavItems as item (toUrl(item.href))}
                                        <Link
                                            href={toUrl(item.href)}
                                            onclick={() =>
                                                (mobileMenuOpen = false)}
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent {url.whenCurrentUrl(
                                                item.href,
                                                url.currentUrl,
                                                activeItemStyles,
                                                '',
                                            ) ?? ''}"
                                        >
                                            {#if item.icon}
                                                <item.icon class="h-5 w-5" />
                                            {/if}
                                            {item.title}
                                        </Link>
                                    {/each}
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    {#each rightNavItems as item (toUrl(item.href))}
                                        <a
                                            href={toUrl(item.href)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center space-x-2 text-sm font-medium"
                                        >
                                            {#if item.icon}
                                                <item.icon class="h-5 w-5" />
                                            {/if}
                                            <span>{item.title}</span>
                                        </a>
                                    {/each}
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}
            </div>

            <Link href={dashboardUrl} class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <div class="hidden h-full lg:flex lg:flex-1">
                <nav class="ml-10 flex h-full items-stretch">
                    <ul class="flex h-full items-stretch space-x-2">
                        {#each mainNavItems as item (toUrl(item.href))}
                            <li class="relative flex h-full items-center">
                                <Link
                                    class="{navLinkStyles} {url.whenCurrentUrl(
                                        item.href,
                                        url.currentUrl,
                                        activeItemStyles,
                                        '',
                                    ) ?? ''} h-9 cursor-pointer px-4"
                                    href={toUrl(item.href)}
                                >
                                    {#if item.icon}
                                        <item.icon class="mr-2 h-4 w-4" />
                                    {/if}
                                    {item.title}
                                </Link>
                                {#if url.isCurrentUrl(item.href, url.currentUrl)}
                                    <div
                                        class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                    ></div>
                                {/if}
                            </li>
                        {/each}
                    </ul>
                </nav>
            </div>

            <div class="ml-auto flex items-center space-x-2">
                <div class="relative flex items-center space-x-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="group h-9 w-9 cursor-pointer"
                    >
                        <Search
                            class="size-5 opacity-80 group-hover:opacity-100"
                        />
                    </Button>

                    <div class="hidden space-x-1 lg:flex">
                        {#each rightNavItems as item (toUrl(item.href))}
                            <Tooltip tip={item.title}>
                                <a
                                    href={toUrl(item.href)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground h-9 w-9 group cursor-pointer"
                                >
                                    <span class="sr-only">{item.title}</span>
                                    <item.icon
                                        class="size-5 opacity-80 group-hover:opacity-100"
                                    />
                                </a>
                            </Tooltip>
                        {/each}
                    </div>
                </div>

                <Dropdown align="end" class="w-56">
                    {#snippet trigger()}
                        <span
                            class="btn btn-ghost btn-square relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                        >
                            <UserAvatar
                                name={auth.user.name}
                                avatar={auth.user.avatar}
                                class="size-8"
                            />
                        </span>
                    {/snippet}
                    {#snippet children({ close })}
                        <UserMenuContent user={auth.user} {close} />
                    {/snippet}
                </Dropdown>

                <TeamSwitcher inHeader={true} />
            </div>
        </div>
    </div>

    {#if breadcrumbs.length > 1}
        <div class="flex w-full border-b border-sidebar-border/70">
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs {breadcrumbs} />
            </div>
        </div>
    {/if}
</div>
