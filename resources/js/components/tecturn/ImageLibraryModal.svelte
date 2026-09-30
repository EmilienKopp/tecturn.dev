<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ListPresentationImagesController from '@/actions/App/Http/Controllers/Presentations/ListPresentationImagesController';
    import { Button } from '@/components/ui/button';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { fetchTeamImages } from '@/lib/tecturn/uploads';
    import type { LibraryImage } from '@/lib/tecturn/uploads';

    let {
        open = $bindable(false),
        onSelect,
    }: {
        open?: boolean;
        /** Called with the chosen image URL; the modal closes itself after. */
        onSelect: (url: string) => void;
    } = $props();

    let images = $state<LibraryImage[]>([]);
    let loading = $state(false);

    // Refetch on every open so freshly uploaded images show up without a reload.
    $effect(() => {
        if (!open) {
            return;
        }

        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        loading = true;

        fetchTeamImages(
            ListPresentationImagesController({
                current_team: currentTeam.slug,
            }).url,
        )
            .then((result) => {
                images = result;
            })
            .finally(() => {
                loading = false;
            });
    });

    function choose(url: string) {
        onSelect(url);
        open = false;
    }
</script>

<Dialog {open} onOpenChange={(value) => (open = value)}>
    <DialogContent class="sm:max-w-2xl">
        <div class="space-y-1">
            <DialogTitle>Choose an image</DialogTitle>
            <DialogDescription>
                Reuse an image from any of your team's presentations.
            </DialogDescription>
        </div>

        {#if loading}
            <div
                class="grid grid-cols-2 gap-3 sm:grid-cols-3"
                data-test="image-library-loading"
            >
                {#each Array(6) as _, index (index)}
                    <div
                        class="aspect-video animate-pulse rounded-md border bg-muted"
                    ></div>
                {/each}
            </div>
        {:else if images.length === 0}
            <div
                class="flex flex-col items-center gap-1 rounded-md border border-dashed p-8 text-center"
                data-test="image-library-empty"
            >
                <p class="text-sm text-muted-foreground">No images yet.</p>
                <p class="text-[11px] text-muted-foreground">
                    Images you upload to any presentation will appear here.
                </p>
            </div>
        {:else}
            <div
                class="grid max-h-[60vh] grid-cols-2 gap-3 overflow-y-auto sm:grid-cols-3"
                data-test="image-library-grid"
            >
                {#each images as image (image.url)}
                    <button
                        type="button"
                        class="group relative flex flex-col overflow-hidden rounded-md border text-left transition hover:ring-2 hover:ring-primary"
                        onclick={() => choose(image.url)}
                        title={image.name}
                        data-test="image-library-item"
                    >
                        <img
                            src={image.url}
                            alt={image.name}
                            class="aspect-video w-full bg-muted object-cover"
                            loading="lazy"
                        />
                        <span
                            class="truncate px-2 py-1 text-[11px] text-muted-foreground"
                        >
                            {image.presentation_name}
                        </span>
                    </button>
                {/each}
            </div>
        {/if}

        <div class="flex justify-end">
            <Button variant="secondary" onclick={() => (open = false)}>
                Cancel
            </Button>
        </div>
    </DialogContent>
</Dialog>
