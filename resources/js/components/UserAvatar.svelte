<script lang="ts">
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from '@/components/ui/avatar';
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

<Avatar class={className}>
    {#if showImage}
        <AvatarImage
            src={avatar}
            alt={name}
            onerror={() => (failedSrc = avatar)}
        />
    {/if}
    <AvatarFallback>{getInitials(name)}</AvatarFallback>
</Avatar>
