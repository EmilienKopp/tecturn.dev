<script lang="ts">
    import { Button, Modal } from 'daisy-svelte';
    import ThemeSelector from '@/components/ThemeSelector.svelte';
    import {
        BRANDING_KEYS,
        BRANDING_LABELS,
        brandingColorsFromTheme,
    } from '@/lib/tecturn/branding';
    import { THEME_OPTIONS, themeState } from '@/lib/theme.svelte';
    import type { ThemeOption } from '@/lib/theme.svelte';
    import type { BrandingColors } from '@/types/auth';

    let {
        open = $bindable(false),
        onSave,
    }: {
        open?: boolean;
        onSave: (branding: BrandingColors) => void;
    } = $props();

    // The On Air pair ships as two concrete themes; 'auto' is an appearance
    // setting, not a palette, so it is replaced by its two resolved themes.
    const themes: ThemeOption[] = [
        { name: 'onair', label: 'On Air', scheme: 'light' },
        { name: 'onair-dark', label: 'On Air Dark', scheme: 'dark' },
        ...THEME_OPTIONS.filter((option) => option.name !== 'auto'),
    ];

    let selectedTheme = $state(themeState().resolvedTheme());

    const preview = $derived(brandingColorsFromTheme(selectedTheme));

    function apply(): void {
        onSave({ ...preview });
        open = false;
    }
</script>

<Modal bind:open class="sm:max-w-2xl">
    {#snippet title()}Pick colors from a theme{/snippet}
    <div class="space-y-4">
        <p class="text-sm text-muted-foreground">
            Fill the six branding slots with a theme's palette. Nothing is
            stored until you save your branding.
        </p>

        <div class="grid gap-4">
            <div
                class="max-h-72 overflow-y-auto pr-1"
                data-test="pick-from-theme-select"
            >
                <ThemeSelector
                    options={themes}
                    value={selectedTheme}
                    onSelect={(name) => (selectedTheme = name)}
                />
            </div>

            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                data-test="pick-from-theme-preview"
            >
                {#each BRANDING_KEYS as key (key)}
                    <div class="flex items-center gap-2">
                        <span
                            class="h-6 w-6 shrink-0 rounded-md border"
                            style="background: {preview[key]}"
                            title={preview[key]}
                        ></span>
                        <span class="text-xs text-muted-foreground">
                            {BRANDING_LABELS[key]}
                        </span>
                    </div>
                {/each}
            </div>
        </div>
    </div>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}>
            Cancel
        </Button>
        <Button onclick={apply} data-test="pick-from-theme-apply">
            Use these colors
        </Button>
    {/snippet}
</Modal>
