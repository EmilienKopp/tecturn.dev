<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Check from 'lucide-svelte/icons/check';
    import ChevronsUpDown from 'lucide-svelte/icons/chevrons-up-down';
    import Plus from 'lucide-svelte/icons/plus';
    import Users from 'lucide-svelte/icons/users';
    import { onMount } from 'svelte';
    import CreateTeamModal from '@/components/CreateTeamModal.svelte';
    import { Dropdown } from 'daisy-svelte';
    import { switchMethod } from '@/routes/teams';
    import type { Team } from '@/types';

    let { inHeader = false }: { inHeader?: boolean } = $props();
    let isMobile = $state(false);

    const currentTeam = $derived(page.props.currentTeam as Team | null);
    const teams = $derived((page.props.teams ?? []) as Team[]);

    const callClickHandler = (handler: unknown, event: MouseEvent) => {
        if (typeof handler === 'function') {
            handler(event);
        }
    };

    onMount(() => {
        const query = window.matchMedia('(max-width: 767px)');
        const update = () => (isMobile = query.matches);

        update();
        query.addEventListener('change', update);

        return () => query.removeEventListener('change', update);
    });

    const switchTeam = (team: Team) => {
        const previousTeamSlug = currentTeam?.slug;

        const options = {
            onFinish: () => {
                if (!previousTeamSlug || typeof window === 'undefined') {
                    router.reload();

                    return;
                }

                const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
                const segment = `/${previousTeamSlug}`;

                if (currentUrl.includes(segment)) {
                    router.visit(currentUrl.replace(segment, `/${team.slug}`), {
                        replace: true,
                    });

                    return;
                }

                router.reload();
            },
        };

        router.visit(switchMethod(team.slug), options);
    };
</script>

<!-- The Dropdown lives inside CreateTeamModal's trigger snippet so the modal
     content sits outside the <details> and survives the dropdown closing. -->
<CreateTeamModal>
    {#snippet children(triggerProps)}
        <Dropdown
            position={inHeader ? 'bottom' : isMobile ? 'bottom' : 'right'}
            align={inHeader ? 'end' : 'start'}
            class={inHeader ? 'w-56' : 'min-w-56 rounded-lg'}
        >
            {#snippet trigger()}
                <span
                    class="btn btn-ghost {inHeader
                        ? 'h-8 gap-1 px-2'
                        : 'has-[>svg]:px-2 w-full justify-start px-2'}"
                    data-test="team-switcher-trigger"
                >
                    <Users
                        class={inHeader
                            ? 'hidden'
                            : 'hidden size-4 shrink-0 group-data-[collapsible=icon]:block'}
                    />
                    <div
                        class={inHeader
                            ? 'grid flex-1 text-left text-sm leading-tight'
                            : 'grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden'}
                    >
                        <span
                            class={inHeader
                                ? 'max-w-[120px] truncate font-medium'
                                : 'truncate font-semibold'}
                        >
                            {currentTeam?.name ?? 'Select team'}
                        </span>
                    </div>
                    <ChevronsUpDown
                        class={inHeader
                            ? 'size-4 opacity-50'
                            : 'ml-auto size-4 group-data-[collapsible=icon]:hidden'}
                    />
                </span>
            {/snippet}

            {#snippet children({ close })}
                <li class="menu-title text-xs text-muted-foreground">Teams</li>

                {#each teams as team (team.id)}
                    <li>
                        <button
                            type="button"
                            class="gap-2 {inHeader ? '' : 'p-2'}"
                            data-test="team-switcher-item"
                            onclick={() => {
                                close();
                                switchTeam(team);
                            }}
                        >
                            {team.name}
                            {#if currentTeam?.id === team.id}
                                <Check
                                    class={inHeader
                                        ? 'ml-auto size-4'
                                        : 'ml-auto h-4 w-4'}
                                />
                            {/if}
                        </button>
                    </li>
                {/each}

                <li class="border-base-300 my-1 border-t"></li>

                <li>
                    <button
                        type="button"
                        class="gap-2 {inHeader ? '' : 'p-2'}"
                        data-test="team-switcher-new-team"
                        onclick={(event) => {
                            close();
                            callClickHandler(triggerProps.onClick, event);
                        }}
                    >
                        <Plus class={inHeader ? 'size-4' : 'h-4 w-4'} />
                        <span class="text-muted-foreground">New team</span>
                    </button>
                </li>
            {/snippet}
        </Dropdown>
    {/snippet}
</CreateTeamModal>
