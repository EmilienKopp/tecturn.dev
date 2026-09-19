<script module lang="ts">
    import { edit } from '@/routes/branding';

    export const layout = {
        breadcrumbs: [
            {
                title: 'Branding settings',
                href: edit(),
            },
        ],
    };
</script>

<script lang="ts">
    import { page, useForm } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import InputError from '@/components/InputError.svelte';
    import ColorField from '@/components/tecturn/ColorField.svelte';
    import GradientModal from '@/components/tecturn/GradientModal.svelte';
    import { Button } from '@/components/ui/button';
    import { Label } from '@/components/ui/label';
    import { isGradientBackground } from '@/lib/tecturn/background';
    import {
        BRANDING_FALLBACK,
        BRANDING_FONT_SIZES,
        BRANDING_FONT_WEIGHTS,
        BRANDING_KEYS,
        BRANDING_LABELS,
    } from '@/lib/tecturn/branding';
    import { FONTS } from '@/lib/tecturn/fonts';
    import { update as updateBranding } from '@/routes/branding';

    const brandingForm = useForm({
        branding: { ...(page.props.auth.user.branding ?? BRANDING_FALLBACK) },
    });

    /** What each color slot drives in the editor, beyond being a swatch. */
    const roleHints: Partial<Record<string, string>> = {
        background: 'Default slide background',
        primary: 'Default text color',
    };

    const backgroundIsGradient = $derived(
        isGradientBackground(brandingForm.branding.background),
    );

    let gradientModalOpen = $state(false);

    function saveBranding(): void {
        brandingForm.patch(updateBranding.url(), { preserveScroll: true });
    }

    function resetBranding(): void {
        brandingForm.branding = { ...BRANDING_FALLBACK };
    }
</script>

<AppHead title="Branding settings" />

<h1 class="sr-only">Branding settings</h1>

<div class="flex flex-col space-y-6">
    <Heading
        variant="small"
        title="Branding"
        description="Your brand colors and typography are the editor's defaults: the background becomes the default slide background, the primary color the default text color, and all six colors appear as one-click shortcuts next to every color picker."
    />

    <div class="grid gap-4 sm:grid-cols-2">
        {#each BRANDING_KEYS as key (key)}
            <div class="grid gap-2">
                <Label for="branding-{key}">
                    {BRANDING_LABELS[key]}
                    {#if roleHints[key]}
                        <span class="ml-1 text-xs text-muted-foreground">
                            · {roleHints[key]}
                        </span>
                    {/if}
                </Label>
                {#if key === 'background'}
                    <div class="flex items-center gap-3">
                        {#if backgroundIsGradient}
                            <div
                                class="h-9 w-16 rounded-md border"
                                style="background: {brandingForm.branding
                                    .background};"
                                title={brandingForm.branding.background}
                                data-test="branding-background-gradient-preview"
                            ></div>
                        {:else}
                            <ColorField
                                id="branding-{key}"
                                class="h-9 w-16 cursor-pointer rounded-md border"
                                value={brandingForm.branding.background}
                                onchange={(color) =>
                                    (brandingForm.branding.background = color)}
                                showSwatches={false}
                                dataTest="branding-{key}"
                            />
                        {/if}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onclick={() => (gradientModalOpen = true)}
                            data-test="branding-background-gradient-button"
                        >
                            Gradient…
                        </Button>
                        {#if backgroundIsGradient}
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onclick={() =>
                                    (brandingForm.branding.background =
                                        BRANDING_FALLBACK.background)}
                                data-test="branding-background-solid-button"
                            >
                                Solid
                            </Button>
                        {/if}
                    </div>
                {:else}
                    <div class="flex items-center gap-3">
                        <ColorField
                            id="branding-{key}"
                            class="h-9 w-16 cursor-pointer rounded-md border"
                            value={brandingForm.branding[key]}
                            onchange={(color) =>
                                (brandingForm.branding[key] = color)}
                            showSwatches={false}
                            dataTest="branding-{key}"
                        />
                        <span class="font-mono text-xs text-muted-foreground">
                            {brandingForm.branding[key]}
                        </span>
                    </div>
                {/if}
                <InputError
                    class="mt-1"
                    message={brandingForm.errors[`branding.${key}`]}
                />
            </div>
        {/each}
    </div>

    <Heading
        variant="small"
        title="Typography"
        description="Default font for new text blocks. Leave on Default to use the editor's built-in styling."
    />

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="grid gap-2">
            <Label for="branding-font-family">Font</Label>
            <select
                id="branding-font-family"
                class="rounded-md border bg-background px-3 py-2 text-sm"
                value={brandingForm.branding.fontFamily ?? ''}
                onchange={(event) =>
                    (brandingForm.branding.fontFamily =
                        event.currentTarget.value || null)}
                data-test="branding-font-family"
            >
                <option value="">Default</option>
                {#each FONTS as font (font.label)}
                    <option value={font.label} style="font-family: {font.stack}"
                        >{font.label}</option
                    >
                {/each}
            </select>
            <InputError message={brandingForm.errors['branding.fontFamily']} />
        </div>

        <div class="grid gap-2">
            <Label for="branding-font-size">Font size</Label>
            <select
                id="branding-font-size"
                class="rounded-md border bg-background px-3 py-2 text-sm"
                value={brandingForm.branding.fontSize ?? ''}
                onchange={(event) =>
                    (brandingForm.branding.fontSize =
                        event.currentTarget.value || null)}
                data-test="branding-font-size"
            >
                <option value="">Default</option>
                {#each BRANDING_FONT_SIZES as size (size)}
                    <option value={size}>{size}</option>
                {/each}
            </select>
            <InputError message={brandingForm.errors['branding.fontSize']} />
        </div>

        <div class="grid gap-2">
            <Label for="branding-font-weight">Font weight</Label>
            <select
                id="branding-font-weight"
                class="rounded-md border bg-background px-3 py-2 text-sm"
                value={brandingForm.branding.fontWeight ?? ''}
                onchange={(event) =>
                    (brandingForm.branding.fontWeight =
                        event.currentTarget.value || null)}
                data-test="branding-font-weight"
            >
                <option value="">Default</option>
                {#each BRANDING_FONT_WEIGHTS as weight (weight)}
                    <option value={weight}>{weight}</option>
                {/each}
            </select>
            <InputError message={brandingForm.errors['branding.fontWeight']} />
        </div>
    </div>

    <div class="flex items-center gap-4">
        <Button
            type="button"
            onclick={saveBranding}
            disabled={brandingForm.processing}
            data-test="save-branding-button">Save branding</Button
        >
        <Button type="button" variant="ghost" onclick={resetBranding}>
            Reset to defaults
        </Button>
    </div>
</div>

<GradientModal
    current={brandingForm.branding.background}
    bind:open={gradientModalOpen}
    onSave={(gradient) => (brandingForm.branding.background = gradient)}
/>
