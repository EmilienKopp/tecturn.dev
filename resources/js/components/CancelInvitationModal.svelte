<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button, Modal } from 'daisy-svelte';
    import { destroy as destroyInvitation } from '@/routes/teams/invitations';
    import type { Team, TeamInvitation } from '@/types';

    let {
        team,
        invitation,
        open = $bindable(),
    }: {
        team: Team;
        invitation: TeamInvitation | null;
        open: boolean;
    } = $props();

    let processing = $state(false);

    const cancelInvitation = () => {
        if (!invitation) {
            return;
        }

        router.visit(destroyInvitation([team.slug, invitation.code]), {
            onStart: () => (processing = true),
            onFinish: () => (processing = false),
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Modal bind:open>
    {#snippet title()}Cancel invitation{/snippet}
    <p class="text-muted-foreground text-sm">
        Are you sure you want to cancel the invitation for <strong
            >{invitation?.email}</strong
        >?
    </p>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}
            >Keep invitation</Button
        >

        <Button
            variant="destructive"
            disabled={processing}
            onclick={cancelInvitation}
            data-test="cancel-invitation-confirm">Cancel invitation</Button
        >
    {/snippet}
</Modal>
