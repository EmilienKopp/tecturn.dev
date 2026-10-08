<script lang="ts">
    import { Avatar } from 'daisy-svelte';
    import { getInitials } from '@/lib/initials';

    let {
        name,
        avatar = null,
        class: className = 'h-8 w-8',
    }: {
        name: string;
        avatar?: string | null;
        class?: string;
    } = $props();

    let failedSrc = $state<string | null>(null);

    const showImage = $derived(
        !!avatar && avatar !== '' && avatar !== failedSrc,
    );
</script>

<Avatar
    src={showImage ? avatar : null}
    alt={name}
    fallback={getInitials(name)}
    class="rounded-full {className}"
    onerror={() => (failedSrc = avatar)}
/>
