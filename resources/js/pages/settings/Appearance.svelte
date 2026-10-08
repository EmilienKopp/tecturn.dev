<script module lang="ts">
    import { edit as editAppearance } from '@/routes/appearance';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: editAppearance(),
            },
        ],
    };
</script>

<script lang="ts">
    import AppearanceTabs from '@/components/AppearanceTabs.svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import ThemeSelector from '@/components/ThemeSelector.svelte';
    import { themeState } from '@/lib/theme.svelte';

    const { theme } = themeState();
</script>

<AppHead title="Appearance settings" />

<h1 class="sr-only">Appearance settings</h1>

<div class="space-y-6">
    <Heading
        variant="small"
        title="Appearance settings"
        description="Update the appearance settings for your account"
    />
    <div class={theme.value === 'auto' ? '' : 'pointer-events-none opacity-50'}>
        <AppearanceTabs />
        {#if theme.value !== 'auto'}
            <p class="text-muted-foreground mt-1 text-xs"></p>
        {/if}
    </div>

    <Heading
        variant="small"
        title="Theme"
        description="Light/dark only applies to the On Air theme."
    />
    <ThemeSelector />
</div>
