<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
    import Heading from '@/components/Heading.svelte';
    import { Button, Modal } from 'daisy-svelte';

    let open = $state(false);
</script>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Delete account"
        description="Delete your account and all of its resources"
    />
    <div
        class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
    >
        <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
            <p class="font-medium">Warning</p>
            <p class="text-sm">
                Please proceed with caution, this cannot be undone.
            </p>
        </div>
        <Button
            variant="destructive"
            data-test="delete-user-button"
            onclick={() => (open = true)}>Delete account</Button
        >
        <Modal bind:open>
            {#snippet title()}Are you sure you want to delete your account?{/snippet}
            <Form
                {...ProfileController.destroy.form()}
                class="space-y-6"
                options={{ preserveScroll: true }}
            >
                {#snippet children({ processing, resetAndClearErrors })}
                    <p class="text-muted-foreground text-sm">
                        Once your account is deleted, all of its resources and
                        data will also be permanently deleted. Please confirm
                        you would like to permanently delete your account.
                    </p>

                    <div class="modal-action gap-2">
                        <Button
                            variant="secondary"
                            onclick={() => {
                                resetAndClearErrors();
                                open = false;
                            }}
                        >
                            Cancel
                        </Button>

                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={processing}
                            data-test="confirm-delete-user-button"
                        >
                            Delete account
                        </Button>
                    </div>
                {/snippet}
            </Form>
        </Modal>
    </div>
</div>
