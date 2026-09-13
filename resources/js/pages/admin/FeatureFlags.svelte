<script module lang="ts">
    import { dashboard, features } from '@/routes/admin';

    export const layout = () => ({
        breadcrumbs: [
            { title: 'Admin', href: dashboard() },
            { title: 'Features', href: features() },
        ],
    });
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';
    import { Switch } from '@/components/ui/switch';
    import { update } from '@/routes/admin/features';

    type FlagOption = { value: string; label: string };
    type FlagValue = boolean | string;

    type FlagDefinition = {
        key: string;
        label: string;
        description: string;
        type: 'boolean' | 'select';
        scope: 'global' | 'team';
        options: FlagOption[];
    };

    type Flag = FlagDefinition & { value: FlagValue };

    type TeamOption = { id: number; name: string; is_personal: boolean };

    let {
        globalFlags = [],
        teams = [],
        selectedTeamId = null,
        teamFlags = [],
    }: {
        globalFlags?: Flag[];
        teams?: TeamOption[];
        selectedTeamId?: number | null;
        teamFlags?: Flag[];
    } = $props();

    // Keys with a write in flight, so we can disable the control that changed.
    let processing = $state<string[]>([]);

    const trackKey = (key: string, teamId: number | null): string =>
        teamId === null ? key : `${teamId}:${key}`;

    const isBusy = (key: string, teamId: number | null = null): boolean =>
        processing.includes(trackKey(key, teamId));

    const setFlag = (
        key: string,
        value: FlagValue,
        teamId: number | null = null,
    ) => {
        const tracked = trackKey(key, teamId);
        processing = [...processing, tracked];
        router.post(
            update().url,
            { key, value, team_id: teamId },
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: (e) => {
                    console.log(`Event:`, e, update().url);
                    processing = processing.filter((p) => p !== tracked);
                },
            },
        );
    };

    const selectedTeamValue = $derived(
        selectedTeamId !== null ? String(selectedTeamId) : '',
    );

    const selectedTeamName = $derived(
        teams.find((team) => team.id === selectedTeamId)?.name ??
            'Choose a team',
    );

    const optionLabel = (flag: Flag): string =>
        flag.options.find((option) => option.value === flag.value)?.label ??
        String(flag.value);

    const selectTeam = (value: string) => {
        router.get(
            features().url,
            { team_id: value },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['teamFlags', 'selectedTeamId'],
            },
        );
    };
</script>

<AppHead title="Features" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-10 p-6">
    <Heading
        variant="small"
        title="Features"
        description="Toggle capabilities globally or for a single team."
    />

    <section class="flex flex-col gap-4">
        <div>
            <h2 class="text-sm font-semibold text-foreground">Global</h2>
            <p class="text-xs text-muted-foreground">
                Applies across the whole platform.
            </p>
        </div>

        <div
            class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card"
        >
            {#each globalFlags as flag (flag.key)}
                <div class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <p class="font-medium text-foreground">{flag.label}</p>
                        <p class="text-xs text-muted-foreground">
                            {flag.description}
                        </p>
                    </div>

                    <div class="shrink-0">
                        {#if flag.type === 'boolean'}
                            <Switch
                                checked={flag.value === true}
                                disabled={isBusy(flag.key)}
                                onCheckedChange={(checked) =>
                                    setFlag(flag.key, checked)}
                            />
                        {:else}
                            <Select
                                type="single"
                                value={String(flag.value)}
                                onValueChange={(value) =>
                                    setFlag(flag.key, value)}
                            >
                                <SelectTrigger
                                    class="w-64"
                                    disabled={isBusy(flag.key)}
                                >
                                    {optionLabel(flag)}
                                </SelectTrigger>
                                <SelectContent>
                                    {#each flag.options as option (option.value)}
                                        <SelectItem
                                            value={option.value}
                                            label={option.label}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    {/each}
                                </SelectContent>
                            </Select>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    </section>

    <section class="flex flex-col gap-4">
        <div>
            <h2 class="text-sm font-semibold text-foreground">Per account</h2>
            <p class="text-xs text-muted-foreground">
                Overrides for a single team. Pick a team to manage its features.
            </p>
        </div>

        <div class="flex flex-col gap-2">
            <Select
                type="single"
                value={selectedTeamValue}
                onValueChange={selectTeam}
            >
                <SelectTrigger class="w-72">
                    {selectedTeamName}
                </SelectTrigger>
                <SelectContent>
                    {#each teams as team (team.id)}
                        <SelectItem value={String(team.id)} label={team.name}>
                            {team.name}
                            {#if team.is_personal}
                                <span class="text-muted-foreground"
                                    >(personal)</span
                                >
                            {/if}
                        </SelectItem>
                    {/each}
                </SelectContent>
            </Select>
        </div>

        {#if selectedTeamId === null}
            <div
                class="rounded-xl border border-dashed border-border bg-card p-8 text-center text-sm text-muted-foreground"
            >
                Choose a team to see its feature overrides.
            </div>
        {:else}
            <div
                class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card"
            >
                {#each teamFlags as flag (flag.key)}
                    <div class="flex items-center justify-between gap-4 p-4">
                        <div class="min-w-0">
                            <p class="font-medium text-foreground">
                                {flag.label}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {flag.description}
                            </p>
                        </div>

                        <div class="shrink-0">
                            <Switch
                                checked={flag.value === true}
                                disabled={isBusy(flag.key, selectedTeamId)}
                                onCheckedChange={(checked) =>
                                    setFlag(flag.key, checked, selectedTeamId)}
                            />
                        </div>
                    </div>
                {/each}
            </div>
        {/if}
    </section>
</div>
