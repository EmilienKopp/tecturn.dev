<script lang="ts">
    import {
        brandingSwatches
        
    } from '@/lib/tecturn/branding';
import type {BrandingSwatch} from '@/lib/tecturn/branding';

    interface Props {
        /** Current color as a hex string (e.g. "#1a2b3c"). */
        value: string;
        /** Called with the new hex value whenever the picker or a swatch changes. */
        onchange: (value: string) => void;
        id?: string;
        /** Classes applied to the native color input. */
        class?: string;
        disabled?: boolean;
        /** Override the shortcut swatches; defaults to the user's branding palette. */
        swatches?: BrandingSwatch[];
        /** Hide the branding shortcut row when false. */
        showSwatches?: boolean;
        dataTest?: string;
    }

    let {
        value,
        onchange,
        id,
        class: className = 'h-8 w-full cursor-pointer rounded-md border',
        disabled = false,
        swatches,
        showSwatches = true,
        dataTest,
    }: Props = $props();

    const shortcuts = $derived(swatches ?? brandingSwatches());

    function isActive(swatchValue: string): boolean {
        return (value ?? '').toLowerCase() === swatchValue.toLowerCase();
    }
</script>

<div class="space-y-1.5">
    <input
        {id}
        type="color"
        class={className}
        {value}
        {disabled}
        oninput={(event) => onchange(event.currentTarget.value)}
        data-test={dataTest}
    />

    {#if showSwatches && shortcuts.length > 0}
        <div
            class="flex flex-wrap items-center gap-1"
            role="group"
            aria-label="Branding colors"
        >
            {#each shortcuts as swatch (swatch.key)}
                <button
                    type="button"
                    class="h-4 w-4 rounded border border-border transition-transform hover:scale-110 {isActive(
                        swatch.value,
                    )
                        ? 'ring-2 ring-ring ring-offset-1'
                        : ''}"
                    style="background-color: {swatch.value}"
                    title={swatch.label}
                    aria-label={swatch.label}
                    {disabled}
                    onclick={() => onchange(swatch.value)}
                ></button>
            {/each}
        </div>
    {/if}
</div>
