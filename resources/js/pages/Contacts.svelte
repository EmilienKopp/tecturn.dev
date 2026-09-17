<script module lang="ts">
    import { index as contactsRoute } from '@/routes/contacts';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Contacts',
                href: contactsIndex(),
            },
        ],
    };
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Search from 'lucide-svelte/icons/search';
    import Users from 'lucide-svelte/icons/users';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import UserAvatar from '@/components/UserAvatar.svelte';
    import { index as contactsIndex } from '@/routes/contacts';
    import {
        destroy as unfollowContact,
        store as followContact,
    } from '@/routes/contacts/follow';

    type Contact = {
        id: number;
        name: string;
        avatar: string;
        handle: string | null;
        social_x_handle: string | null;
        social_github_handle: string | null;
        talks_count: number;
        total_viewers: number;
        total_reactions: number;
        followers_count: number;
        following_count: number;
        is_following: boolean;
    };

    type RelationshipContact = {
        id: number;
        name: string;
        avatar: string;
        handle: string | null;
        social_x_handle: string | null;
        social_github_handle: string | null;
        followed_at: string | null;
    };

    type FollowedTalk = {
        id: number;
        name: string;
        updated_at: string | null;
        last_presented_at: string | null;
        viewer_count: number;
        reaction_total: number;
        user: {
            id: number;
            name: string;
            avatar: string;
            handle: string | null;
            social_x_handle: string | null;
            social_github_handle: string | null;
        };
    };

    let {
        search = '',
        results = [],
        following = [],
        followers = [],
        followedTalks = [],
    }: {
        search?: string;
        results?: Contact[];
        following?: RelationshipContact[];
        followers?: RelationshipContact[];
        followedTalks?: FollowedTalk[];
    } = $props();

    let searchTerm = $state(search);

    const submitSearch = (event: SubmitEvent) => {
        event.preventDefault();

        router.get(
            contactsRoute().url,
            { search: searchTerm },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    const toggleFollow = (contact: Contact, isFollowing: boolean) => {
        const route = isFollowing
            ? unfollowContact(contact.id)
            : followContact(contact.id);

        router.visit(route.url, {
            method: route.method,
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleLabel = (contact: {
        handle: string | null;
        social_x_handle: string | null;
        social_github_handle: string | null;
    }): string => {
        if (contact.handle) {
            return `@${contact.handle}`;
        }

        if (contact.social_x_handle) {
            return `X @${contact.social_x_handle}`;
        }

        if (contact.social_github_handle) {
            return `GitHub @${contact.social_github_handle}`;
        }

        return 'No public handle yet';
    };

    const formatWhen = (value: string | null): string => {
        if (!value) {
            return 'Not presented yet';
        }

        return new Date(value).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    const currentUserId = $derived(page.props.auth.user.id as number);
</script>

<AppHead title="Contacts" />

<div class="mx-auto flex w-full max-w-7xl flex-col gap-8 p-6">
    <div
        class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
    >
        <Heading
            variant="small"
            title="Contacts"
            description="Find people by handle, follow them, and keep up with their public talks."
        />

        <form
            class="flex w-full max-w-md items-center gap-2"
            onsubmit={submitSearch}
        >
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    bind:value={searchTerm}
                    class="pl-9"
                    placeholder="Search by name, @handle, GitHub, or X"
                    data-test="contacts-search-input"
                />
            </div>
            <Button
                type="submit"
                variant="outline"
                data-test="contacts-search-button"
            >
                Search
            </Button>
        </form>
    </div>

    <div class="grid gap-8 xl:grid-cols-[1.7fr_1fr]">
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-foreground">
                    {search.trim() === ''
                        ? 'Discover people'
                        : 'Search results'}
                </h2>
                <Badge variant="secondary">{results.length}</Badge>
            </div>

            {#if results.length > 0}
                <div class="grid gap-4 md:grid-cols-2">
                    {#each results as contact (contact.id)}
                        <article
                            class="flex flex-col gap-4 rounded-xl border border-border bg-card p-5"
                            data-test="contact-card"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <UserAvatar
                                        name={contact.name}
                                        avatar={contact.avatar}
                                        class="h-12 w-12 shrink-0"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-medium text-foreground"
                                        >
                                            {contact.name}
                                        </p>
                                        <p
                                            class="truncate text-sm text-muted-foreground"
                                        >
                                            {handleLabel(contact)}
                                        </p>
                                    </div>
                                </div>

                                {#if contact.id !== currentUserId}
                                    <Button
                                        size="sm"
                                        variant={contact.is_following
                                            ? 'outline'
                                            : 'default'}
                                        onclick={() =>
                                            toggleFollow(
                                                contact,
                                                contact.is_following,
                                            )}
                                        data-test="contact-follow-button"
                                    >
                                        {contact.is_following
                                            ? 'Following'
                                            : 'Follow'}
                                    </Button>
                                {/if}
                            </div>

                            <div class="grid grid-cols-3 gap-2 text-sm">
                                <div class="rounded-lg bg-accent/40 px-3 py-2">
                                    <p
                                        class="font-mono text-lg text-foreground"
                                    >
                                        {contact.talks_count}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Talks
                                    </p>
                                </div>
                                <div class="rounded-lg bg-accent/40 px-3 py-2">
                                    <p
                                        class="font-mono text-lg text-foreground"
                                    >
                                        {contact.total_viewers}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        People reached
                                    </p>
                                </div>
                                <div class="rounded-lg bg-accent/40 px-3 py-2">
                                    <p
                                        class="font-mono text-lg text-foreground"
                                    >
                                        {contact.total_reactions}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        Reactions
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap gap-2 text-xs text-muted-foreground"
                            >
                                <span>{contact.followers_count} followers</span>
                                <span>•</span>
                                <span>{contact.following_count} following</span>
                                {#if contact.social_x_handle}
                                    <span>•</span>
                                    <span>X @{contact.social_x_handle}</span>
                                {/if}
                                {#if contact.social_github_handle}
                                    <span>•</span>
                                    <span
                                        >GitHub @{contact.social_github_handle}</span
                                    >
                                {/if}
                            </div>
                        </article>
                    {/each}
                </div>
            {:else}
                <div
                    class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border bg-card px-6 py-14 text-center"
                >
                    <Users class="h-6 w-6 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        No people matched that search yet.
                    </p>
                </div>
            {/if}

            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-foreground">
                        Talks from people you follow
                    </h2>
                    <Badge variant="secondary">{followedTalks.length}</Badge>
                </div>

                {#if followedTalks.length > 0}
                    <div class="grid gap-4 md:grid-cols-2">
                        {#each followedTalks as talk (talk.id)}
                            <article
                                class="flex flex-col gap-4 rounded-xl border border-border bg-card p-5"
                                data-test="followed-talk-card"
                            >
                                <div class="flex items-center gap-3">
                                    <UserAvatar
                                        name={talk.user.name}
                                        avatar={talk.user.avatar}
                                        class="h-10 w-10"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-medium text-foreground"
                                        >
                                            {talk.name}
                                        </p>
                                        <p
                                            class="truncate text-sm text-muted-foreground"
                                        >
                                            {talk.user.name} · {handleLabel(
                                                talk.user,
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div
                                        class="rounded-lg bg-accent/40 px-3 py-2"
                                    >
                                        <p
                                            class="font-mono text-lg text-foreground"
                                        >
                                            {talk.viewer_count}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Viewers
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-lg bg-accent/40 px-3 py-2"
                                    >
                                        <p
                                            class="font-mono text-lg text-foreground"
                                        >
                                            {talk.reaction_total}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            Reactions
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-muted-foreground">
                                    Last presented {formatWhen(
                                        talk.last_presented_at ??
                                            talk.updated_at,
                                    )}
                                </p>
                            </article>
                        {/each}
                    </div>
                {:else}
                    <p
                        class="rounded-xl border border-dashed border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        Follow people to see their public talks and stats here.
                    </p>
                {/if}
            </div>
        </section>

        <aside class="flex flex-col gap-6">
            <section
                class="flex flex-col gap-3 rounded-xl border border-border bg-card p-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-foreground">
                        Following
                    </h2>
                    <Badge variant="secondary">{following.length}</Badge>
                </div>

                {#if following.length > 0}
                    <ul class="flex flex-col gap-3">
                        {#each following as contact (contact.id)}
                            <li
                                class="flex items-center gap-3"
                                data-test="following-row"
                            >
                                <UserAvatar
                                    name={contact.name}
                                    avatar={contact.avatar}
                                    class="h-10 w-10"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-foreground"
                                    >
                                        {contact.name}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {handleLabel(contact)}
                                    </p>
                                </div>
                            </li>
                        {/each}
                    </ul>
                {:else}
                    <p class="text-sm text-muted-foreground">
                        You're not following anyone yet.
                    </p>
                {/if}
            </section>

            <section
                class="flex flex-col gap-3 rounded-xl border border-border bg-card p-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-foreground">
                        Followers
                    </h2>
                    <Badge variant="secondary">{followers.length}</Badge>
                </div>

                {#if followers.length > 0}
                    <ul class="flex flex-col gap-3">
                        {#each followers as contact (contact.id)}
                            <li
                                class="flex items-center gap-3"
                                data-test="follower-row"
                            >
                                <UserAvatar
                                    name={contact.name}
                                    avatar={contact.avatar}
                                    class="h-10 w-10"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-foreground"
                                    >
                                        {contact.name}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {handleLabel(contact)}
                                    </p>
                                </div>
                            </li>
                        {/each}
                    </ul>
                {:else}
                    <p class="text-sm text-muted-foreground">
                        No followers yet.
                    </p>
                {/if}
            </section>
        </aside>
    </div>
</div>
