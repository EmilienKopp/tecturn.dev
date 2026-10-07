<script lang="ts">
    import { PasswordInput } from 'daisy-svelte';

    let { value = $bindable(''), ...rest } = $props();
</script>

<PasswordInput bind:value {...rest} />
