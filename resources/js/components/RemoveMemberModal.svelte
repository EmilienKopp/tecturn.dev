<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Button, Modal } from 'daisy-svelte';
    import { destroy as destroyMember } from '@/routes/teams/members';
    import type { Team, TeamMember } from '@/types';

    let {
        team,
        member,
        open = $bindable(),
    }: {
        team: Team;
        member: TeamMember | null;
        open: boolean;
    } = $props();

    let processing = $state(false);

    const removeMember = () => {
        if (!member) {
            return;
        }

        router.visit(destroyMember([team.slug, member.id]), {
            onStart: () => (processing = true),
            onFinish: () => (processing = false),
            onSuccess: () => {
                open = false;
            },
        });
    };
</script>

<Modal bind:open>
    {#snippet title()}Remove team member{/snippet}
    <p class="text-muted-foreground text-sm">
        Are you sure you want to remove <strong>{member?.name}</strong> from this
        team?
    </p>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}
            >Cancel</Button
        >

        <Button
            variant="destructive"
            disabled={processing}
            onclick={removeMember}
            data-test="remove-member-confirm">Remove member</Button
        >
    {/snippet}
</Modal>
