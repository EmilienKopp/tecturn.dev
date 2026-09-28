<script lang="ts">
    import X from 'lucide-svelte/icons/x';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { DEFAULT_REACTIONS, MAX_REACTIONS } from '@/lib/tecturn/reactions';

    let {
        reactions,
        showReactions,
        open = $bindable(false),
        onSave,
    }: {
        reactions: string[];
        showReactions: boolean;
        open?: boolean;
        onSave: (next: { showReactions: boolean; reactions: string[] }) => void;
    } = $props();

    // Staged edits — only committed on Save.
    let enabled = $state(false);
    let list = $state<string[]>([]);
    let draft = $state('');

    const atLimit = $derived(list.length >= MAX_REACTIONS);
    const trimmedDraft = $derived(draft.trim());
    const canAdd = $derived(
        trimmedDraft !== '' && !list.includes(trimmedDraft) && !atLimit,
    );

    function seed() {
        enabled = showReactions;
        list = reactions.length ? [...reactions] : [...DEFAULT_REACTIONS];
        draft = '';
    }

    // Re-seed on open. The editor opens the modal by setting `open` directly
    // (bind:open), which bypasses onOpenChange, so watch the flag itself.
    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            seed();
        }

        wasOpen = open;
    });

    function handleOpenChange(value: boolean) {
        open = value;
    }

    function addReaction() {
        if (!canAdd) {
            return;
        }

        list = [...list, trimmedDraft];
        draft = '';
    }

    function removeReaction(index: number) {
        list = list.filter((_, i) => i !== index);
    }

    function resetToDefaults() {
        list = [...DEFAULT_REACTIONS];
    }

    function onDraftKeydown(event: KeyboardEvent) {
        if (event.key === 'Enter') {
            event.preventDefault();
            addReaction();
        }
    }

    function save() {
        onSave({
            showReactions: enabled,
            // An empty set falls back to the defaults, matching the server.
            reactions: list.length ? list : [...DEFAULT_REACTIONS],
        });
        open = false;
    }
</script>

<Dialog {open} onOpenChange={handleOpenChange}>
    <DialogContent class="sm:max-w-lg">
        <div class="space-y-3">
            <DialogTitle>Reactions</DialogTitle>
            <DialogDescription>
                The emojis the audience can tap. Keep the defaults or set your
                own — up to {MAX_REACTIONS}.
            </DialogDescription>
        </div>

        <div class="grid gap-4 my-4">
            <label
                class="flex items-center gap-2 text-sm"
                for="reactions-enabled"
            >
                <Checkbox
                    id="reactions-enabled"
                    bind:checked={enabled}
                    data-test="reactions-enabled"
                />
                Show reactions
            </label>

            <div class="grid gap-2">
                <div class="flex items-baseline justify-between">
                    <Label>Reactions</Label>
                    <span class="text-xs text-muted-foreground tabular-nums">
                        {list.length} / {MAX_REACTIONS}
                    </span>
                </div>

                {#if list.length}
                    <div
                        class="flex flex-wrap gap-2"
                        data-test="reactions-list"
                    >
                        {#each list as emoji, index (emoji)}
                            <span
                                class="group flex items-center gap-1 rounded-md border bg-muted/40 py-1 pr-1 pl-2 text-xl"
                            >
                                {emoji}
                                <button
                                    type="button"
                                    class="rounded p-0.5 text-muted-foreground hover:bg-accent hover:text-destructive"
                                    onclick={() => removeReaction(index)}
                                    aria-label="Remove {emoji}"
                                    data-test="reaction-remove"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        {/each}
                    </div>
                {:else}
                    <p class="text-xs text-muted-foreground">
                        No reactions — the defaults will be used.
                    </p>
                {/if}
            </div>

            <div class="flex items-end gap-2">
                <div class="grid flex-1 gap-2">
                    <Label for="reaction-add">Add a reaction</Label>
                    <Input
                        id="reaction-add"
                        bind:value={draft}
                        onkeydown={onDraftKeydown}
                        maxlength={2}
                        autocomplete="off"
                        disabled={atLimit}
                        data-test="reaction-input"
                    />
                </div>
                <Button
                    variant="secondary"
                    onclick={addReaction}
                    disabled={!canAdd}
                    data-test="reaction-add-button"
                >
                    Add
                </Button>
            </div>

            {#if atLimit}
                <p class="text-xs text-muted-foreground">
                    That's the maximum of {MAX_REACTIONS}. Remove one to add
                    another.
                </p>
            {/if}
        </div>

        <DialogFooter class="gap-2 sm:justify-between mx-3">
            <Button
                variant="ghost"
                onclick={resetToDefaults}
                data-test="reactions-reset"
            >
                Reset to defaults
            </Button>
            <div class="flex gap-2">
                <Button variant="secondary" onclick={() => (open = false)}>
                    Cancel
                </Button>
                <Button onclick={save} data-test="reactions-save">Save</Button>
            </div>
        </DialogFooter>
    </DialogContent>
</Dialog>
