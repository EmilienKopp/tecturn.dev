<script lang="ts">
    import type { Snippet } from 'svelte';
    interface Props {
        children: Snippet;
        value?: string;
    }
    let { children, value, ...rest }: Props = $props();
</script>

<select
    class="rounded-md border bg-background px-3 py-2 text-sm"
    {...rest}
    bind:value
>
    {@render children()}
</select>
