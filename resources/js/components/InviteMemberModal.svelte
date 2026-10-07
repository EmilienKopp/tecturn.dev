<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import { Button, Input, Modal, Select } from 'daisy-svelte';
    import { store as storeInvitation } from '@/routes/teams/invitations';
    import type { RoleOption, Team } from '@/types';

    let {
        team,
        availableRoles,
        open = $bindable(),
    }: {
        team: Team;
        availableRoles: RoleOption[];
        open: boolean;
    } = $props();

    let inviteRole = $state<RoleOption['value']>('member');
    let formKey = $state(0);

    const roleOptions = $derived(
        availableRoles.map((role) => ({
            value: role.value,
            name: role.label,
        })),
    );

    function handleClose() {
        inviteRole = 'member';
        formKey++;
    }
</script>

<Modal bind:open onclose={handleClose}>
    {#snippet title()}Invite a team member{/snippet}
    {#key formKey}
        <Form
            {...storeInvitation.form(team.slug)}
            class="space-y-6"
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <p class="text-muted-foreground text-sm">
                    Send an invitation to join this team.
                </p>

                <div class="grid gap-4">
                    <Input
                        label="Email address"
                        name="email"
                        type="email"
                        placeholder="colleague@example.com"
                        required
                        error={errors.email}
                        data-test="invite-email"
                    />

                    <Select
                        label="Role"
                        name="role"
                        placeholder="Select a role"
                        options={roleOptions}
                        bind:value={inviteRole}
                        error={errors.role}
                        data-test="invite-role"
                    />
                </div>

                <div class="modal-action gap-2">
                    <Button variant="secondary" onclick={() => (open = false)}>
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        disabled={processing}
                        data-test="invite-submit">Send invitation</Button
                    >
                </div>
            {/snippet}
        </Form>
    {/key}
</Modal>
