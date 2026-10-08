<script lang="ts">
    import { Alert } from 'daisy-svelte';

    let {
        errors = [],
        title = 'Something went wrong.',
    }: {
        errors: string[];
        title?: string;
    } = $props();
</script>

<Alert variant="error" {title} messages={errors} />
