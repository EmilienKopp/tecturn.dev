<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Button, Input, Modal } from 'daisy-svelte';
    import { destroy } from '@/routes/teams';
    import type { Team } from '@/types';

    let {
        team,
        open = $bindable(),
    }: {
        team: Team;
        open: boolean;
    } = $props();

    let confirmationName = $state('');
    let formKey = $state(0);

    const canDeleteTeam = $derived(confirmationName === team.name);

    function handleClose() {
        confirmationName = '';
        formKey++;
    }
</script>

<Modal bind:open onclose={handleClose}>
    {#snippet title()}Are you sure?{/snippet}
    {#key formKey}
        <Form
            {...destroy.form(team.slug)}
            class="space-y-6"
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <p class="text-muted-foreground text-sm">
                    This action cannot be undone. This will permanently delete
                    the team
                    <strong>"{team.name}"</strong>.
                </p>

                <div class="space-y-4">
                    <Input
                        label={`Type "${team.name}" to confirm`}
                        name="name"
                        bind:value={confirmationName}
                        placeholder="Enter team name"
                        autocomplete="off"
                        error={errors.name}
                        data-test="delete-team-name"
                    />
                </div>

                <div class="modal-action gap-2">
                    <Button variant="secondary" onclick={() => (open = false)}>
                        Cancel
                    </Button>

                    <Button
                        variant="destructive"
                        type="submit"
                        disabled={!canDeleteTeam || processing}
                        data-test="delete-team-confirm"
                    >
                        Delete team
                    </Button>
                </div>
            {/snippet}
        </Form>
    {/key}
</Modal>
