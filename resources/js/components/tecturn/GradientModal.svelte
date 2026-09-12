<script lang="ts">
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Label } from '@/components/ui/label';
    import {
        buildLinearGradient,
        MAX_GRADIENT_STOPS,
        MIN_GRADIENT_STOPS,
        parseLinearGradient,
    } from '@/lib/tecturn/background';

    let {
        current,
        open = $bindable(false),
        onSave,
    }: {
        /** The slide's current background, so an existing gradient re-opens. */
        current: string | null;
        open?: boolean;
        onSave: (gradient: string) => void;
    } = $props();

    // Staged edits — only committed on Save.
    let angle = $state(135);
    let stops = $state<string[]>(['#0f2027', '#2c5364']);

    // A color <input> only holds hex; fall back to a neutral hex for anything
    // parsed that isn't (named colors, rgb()), keeping the picker usable.
    const toHex = (color: string): string =>
        /^#[0-9a-f]{6}$/i.test(color.trim()) ? color.trim() : '#888888';

    function seed() {
        const parsed = parseLinearGradient(current);

        if (parsed) {
            angle = parsed.angle;
            stops = parsed.colors.slice(0, MAX_GRADIENT_STOPS).map(toHex);

            while (stops.length < MIN_GRADIENT_STOPS) {
                stops = [...stops, '#ffffff'];
            }

            return;
        }

        // Seed a solid color as the first stop so editing feels continuous.
        const base = current && /^#[0-9a-f]{6}$/i.test(current) ? current : '#0f2027';
        angle = 135;
        stops = [base, '#2c5364'];
    }

    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            seed();
        }

        wasOpen = open;
    });

    const preview = $derived(buildLinearGradient(angle, stops));

    function setStop(index: number, value: string) {
        stops = stops.map((stop, i) => (i === index ? value : stop));
    }

    function addStop() {
        if (stops.length < MAX_GRADIENT_STOPS) {
            stops = [...stops, '#ffffff'];
        }
    }

    function removeStop(index: number) {
        if (stops.length > MIN_GRADIENT_STOPS) {
            stops = stops.filter((_, i) => i !== index);
        }
    }

    function save() {
        onSave(preview);
        open = false;
    }
</script>

<Dialog {open} onOpenChange={(value) => (open = value)}>
    <DialogContent class="sm:max-w-md">
        <div class="space-y-3">
            <DialogTitle>Gradient background</DialogTitle>
            <DialogDescription>
                Pick up to {MAX_GRADIENT_STOPS} colors and a direction.
            </DialogDescription>
        </div>

        <div class="grid gap-4">
            <div
                class="h-24 w-full rounded-md border"
                style="background: {preview}"
                data-test="gradient-preview"
            ></div>

            <div class="grid gap-2">
                <Label for="gradient-angle" class="text-xs">
                    Angle: {angle}°
                </Label>
                <input
                    id="gradient-angle"
                    type="range"
                    min="0"
                    max="360"
                    step="1"
                    bind:value={angle}
                    class="w-full cursor-pointer"
                    data-test="gradient-angle"
                />
            </div>

            <div class="grid gap-2">
                <Label class="text-xs">Colors</Label>
                {#each stops as stop, index (index)}
                    <div class="flex items-center gap-2">
                        <input
                            type="color"
                            value={toHex(stop)}
                            oninput={(event) =>
                                setStop(index, event.currentTarget.value)}
                            class="h-8 w-12 cursor-pointer rounded border bg-transparent"
                            data-test="gradient-stop-{index}"
                        />
                        <span class="flex-1 font-mono text-xs text-muted-foreground">
                            {stop}
                        </span>
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={stops.length <= MIN_GRADIENT_STOPS}
                            onclick={() => removeStop(index)}
                            data-test="gradient-remove-{index}"
                        >
                            Remove
                        </Button>
                    </div>
                {/each}

                {#if stops.length < MAX_GRADIENT_STOPS}
                    <Button
                        variant="outline"
                        size="sm"
                        class="w-full"
                        onclick={addStop}
                        data-test="gradient-add-stop"
                    >
                        Add color
                    </Button>
                {/if}
            </div>
        </div>

        <DialogFooter class="gap-2">
            <Button variant="secondary" onclick={() => (open = false)}>
                Cancel
            </Button>
            <Button onclick={save} data-test="gradient-save">Apply gradient</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
