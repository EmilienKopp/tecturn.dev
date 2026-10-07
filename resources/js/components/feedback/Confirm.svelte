<script lang="ts">
    import type { ComponentProps } from 'svelte';
    import { Button, Modal } from 'daisy-svelte';

    type Variant = NonNullable<ComponentProps<typeof Button>['variant']>;

    let deleteDialogOpen = $state(false);
    let modalTitle = $state('');
    let text = $state('');
    let onConfirm = $state(() => {});
    let variant = $state<Variant>('default');
    let action = $state('OK');

    export function confirm({
        title: confirmTitle,
        text: confirmText,
        onConfirm: callback,
        variant: confirmVariant,
        action: confirmAction,
    }: {
        title: string;
        text: string;
        onConfirm?: () => void;
        variant?: Variant;
        action?: string;
    }) {
        modalTitle = confirmTitle;
        text = confirmText;
        onConfirm = () => {
            callback?.();
            deleteDialogOpen = false;
        };
        variant = confirmVariant ?? 'default';
        action = confirmAction ?? 'OK';

        deleteDialogOpen = true;
    }
</script>

<Modal bind:open={deleteDialogOpen}>
    {#snippet title()}{modalTitle}{/snippet}
    <p class="text-muted-foreground text-sm">
        {text}
    </p>
    {#snippet actions()}
        <Button
            variant="base"
            outline
            onclick={() => (deleteDialogOpen = false)}
        >
            Cancel
        </Button>
        <Button {variant} onclick={onConfirm} data-test="slide-delete-confirm">
            {action}
        </Button>
    {/snippet}
</Modal>
