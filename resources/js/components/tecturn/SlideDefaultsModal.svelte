<script lang="ts">
    import RotateCcw from 'lucide-svelte/icons/rotate-ccw';
    import { toast } from 'svelte-sonner';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import { FONTS } from '@/lib/tecturn/fonts';
    import { slideDefaults } from '@/lib/tecturn/slide-defaults.svelte';

    let { open = $bindable(false) }: { open?: boolean } = $props();

    const fontSizes = ['1rem', '1.5rem', '2rem', '2.5rem', '3rem', '4rem'];
    const fontWeights = ['normal', 'medium', 'semibold', 'bold'];

    const defaults = $derived(slideDefaults.get());

    const handleReset = () => {
        slideDefaults.reset();
        toast.success('Defaults reset');
    };
</script>

<Dialog bind:open>
    <DialogContent class="max-w-md">
        <div class="space-y-4">
            <div>
                <DialogTitle>Slide Defaults</DialogTitle>
                <DialogDescription>
                    Set default styles for new slides and text blocks. These
                    settings are stored in your browser only.
                </DialogDescription>
            </div>

            <div class="space-y-3">
                <div class="space-y-1.5">
                    <Label for="default-background" class="text-sm"
                        >Default slide background</Label
                    >
                    <input
                        id="default-background"
                        type="color"
                        class="h-9 w-full cursor-pointer rounded-md border"
                        value={defaults.background ?? '#ffffff'}
                        oninput={(event) =>
                            slideDefaults.setBackground(
                                event.currentTarget.value,
                            )}
                        data-test="default-background"
                    />
                </div>

                <div class="space-y-1.5">
                    <Label for="default-font-size" class="text-sm"
                        >Default font size</Label
                    >
                    <select
                        id="default-font-size"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        value={defaults.fontSize ?? ''}
                        onchange={(event) =>
                            slideDefaults.setFontSize(
                                event.currentTarget.value || null,
                            )}
                        data-test="default-font-size"
                    >
                        <option value="">Default</option>
                        {#each fontSizes as size (size)}
                            <option value={size}>{size}</option>
                        {/each}
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="default-font-weight" class="text-sm"
                        >Default font weight</Label
                    >
                    <select
                        id="default-font-weight"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        value={defaults.fontWeight ?? ''}
                        onchange={(event) =>
                            slideDefaults.setFontWeight(
                                event.currentTarget.value || null,
                            )}
                        data-test="default-font-weight"
                    >
                        <option value="">Default</option>
                        {#each fontWeights as weight (weight)}
                            <option value={weight}>{weight}</option>
                        {/each}
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="default-font-family" class="text-sm"
                        >Default font</Label
                    >
                    <select
                        id="default-font-family"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        value={defaults.fontFamily ?? ''}
                        onchange={(event) =>
                            slideDefaults.setFontFamily(
                                event.currentTarget.value || null,
                            )}
                        data-test="default-font-family"
                    >
                        <option value="">Default</option>
                        {#each FONTS as font (font.label)}
                            <option
                                value={font.label}
                                style="font-family: {font.stack}"
                                >{font.label}</option
                            >
                        {/each}
                    </select>
                </div>

                <div class="space-y-1.5">
                    <Label for="default-color" class="text-sm"
                        >Default text color</Label
                    >
                    <input
                        id="default-color"
                        type="color"
                        class="h-9 w-full cursor-pointer rounded-md border"
                        value={defaults.color ?? '#000000'}
                        oninput={(event) =>
                            slideDefaults.setColor(event.currentTarget.value)}
                        data-test="default-color"
                    />
                </div>
            </div>
        </div>

        <DialogFooter>
            <Button
                variant="outline"
                onclick={handleReset}
                data-test="reset-slide-defaults"
            >
                <RotateCcw class="h-4 w-4" /> Reset to Defaults
            </Button>
            <Button onclick={() => (open = false)} data-test="close-defaults">
                Done
            </Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
