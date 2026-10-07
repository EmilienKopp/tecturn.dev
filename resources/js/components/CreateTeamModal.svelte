<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import { Button, Input, Modal } from 'daisy-svelte';
    import { store } from '@/routes/teams';

    type TriggerProps = {
        onClick?: (event: MouseEvent) => void;
        [key: string]: unknown;
    };

    let {
        children: trigger,
    }: {
        children?: Snippet<[TriggerProps]>;
    } = $props();

    let open = $state(false);
    let formKey = $state(0);

    function handleClose() {
        formKey++;
    }
</script>

{@render trigger?.({ onClick: () => (open = true) })}

<Modal bind:open onclose={handleClose}>
    {#snippet title()}Create a new team{/snippet}
    {#key formKey}
        <Form
            {...store.form()}
            class="space-y-6"
            onSuccess={() => (open = false)}
        >
            {#snippet children({ errors, processing })}
                <p class="text-muted-foreground text-sm">
                    Create a new team to collaborate with others.
                </p>

                <Input
                    label="Team name"
                    name="name"
                    placeholder="My team"
                    required
                    error={errors.name}
                    data-test="create-team-name"
                />

                <div class="modal-action gap-2">
                    <Button variant="secondary" onclick={() => (open = false)}>
                        Cancel
                    </Button>

                    <Button
                        type="submit"
                        disabled={processing}
                        data-test="create-team-submit">Create team</Button
                    >
                </div>
            {/snippet}
        </Form>
    {/key}
</Modal>
