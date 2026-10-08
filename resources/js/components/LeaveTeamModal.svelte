<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button, Modal } from 'daisy-svelte';
    import { leave as leaveTeamAction } from '@/routes/teams';
    import type { Team } from '@/types';

    let {
        team,
        open = $bindable(),
    }: {
        team: Team | null;
        open: boolean;
    } = $props();

    let processing = $state(false);

    const leaveTeam = () => {
        if (!team) {
            return;
        }

        router.visit(leaveTeamAction(team.slug), {
            onStart: () => (processing = true),
            onFinish: () => (processing = false),
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Modal bind:open>
    {#snippet title()}Leave team{/snippet}
    <p class="text-muted-foreground text-sm">
        Are you sure you want to leave <strong>{team?.name}</strong>?
    </p>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}
            >Cancel</Button
        >

        <Button
            variant="destructive"
            disabled={processing}
            onclick={leaveTeam}
            data-test="leave-team-confirm">Leave team</Button
        >
    {/snippet}
</Modal>
