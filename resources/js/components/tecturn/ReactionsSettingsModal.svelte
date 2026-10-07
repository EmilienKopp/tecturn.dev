<script lang="ts">
    import X from 'lucide-svelte/icons/x';
    import { Button, Checkbox, Input, Modal } from 'daisy-svelte';
    import {
        DEFAULT_REACTIONS,
        MAX_FREE_TEXT_LENGTH,
        MAX_REACTIONS,
    } from '@/lib/tecturn/reactions';

    let {
        reactions,
        showReactions,
        allowFreeText,
        freeTextMaxLength,
        open = $bindable(false),
        onSave,
    }: {
        reactions: string[];
        showReactions: boolean;
        allowFreeText: boolean;
        freeTextMaxLength: number;
        open?: boolean;
        onSave: (next: {
            showReactions: boolean;
            reactions: string[];
            allowFreeText: boolean;
            freeTextMaxLength: number;
        }) => void;
    } = $props();

    // Staged edits — only committed on Save.
    let enabled = $state(false);
    let list = $state<string[]>([]);
    let draft = $state('');
    let freeText = $state(false);
    let maxLength = $state(20);

    const atLimit = $derived(list.length >= MAX_REACTIONS);
    const trimmedDraft = $derived(draft.trim());
    const canAdd = $derived(
        trimmedDraft !== '' && !list.includes(trimmedDraft) && !atLimit,
    );

    function seed() {
        enabled = showReactions;
        list = reactions.length ? [...reactions] : [...DEFAULT_REACTIONS];
        draft = '';
        freeText = allowFreeText;
        maxLength = freeTextMaxLength;
    }

    function clampMaxLength() {
        maxLength = Math.max(
            1,
            Math.min(MAX_FREE_TEXT_LENGTH, Math.round(maxLength) || 1),
        );
    }

    // Re-seed on open. The editor opens the modal by setting `open` directly
    // (bind:open), so watch the flag itself.
    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            seed();
        }

        wasOpen = open;
    });

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
        clampMaxLength();
        onSave({
            showReactions: enabled,
            // An empty set falls back to the defaults, matching the server.
            reactions: list.length ? list : [...DEFAULT_REACTIONS],
            allowFreeText: freeText,
            freeTextMaxLength: maxLength,
        });
        open = false;
    }
</script>

<Modal bind:open class="sm:max-w-lg">
    {#snippet title()}Reactions{/snippet}
    <p class="text-muted-foreground text-sm">
        The emojis the audience can tap. Keep the defaults or set your own — up
        to {MAX_REACTIONS}.
    </p>

    <div class="grid gap-4 my-4">
        <label class="flex items-center gap-2 text-sm" for="reactions-enabled">
            <Checkbox
                id="reactions-enabled"
                bind:checked={enabled}
                data-test="reactions-enabled"
            />
            Show reactions
        </label>

        <div class="grid gap-2">
            <div class="flex items-baseline justify-between">
                <span class="label">Reactions</span>
                <span class="text-xs text-muted-foreground tabular-nums">
                    {list.length} / {MAX_REACTIONS}
                </span>
            </div>

            {#if list.length}
                <div class="flex flex-wrap gap-2" data-test="reactions-list">
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
            <Input
                label="Add a reaction"
                id="reaction-add"
                fieldsetClass="flex-1"
                bind:value={draft}
                onkeydown={onDraftKeydown}
                maxlength={2}
                autocomplete="off"
                disabled={atLimit}
                data-test="reaction-input"
            />
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

        <div class="grid gap-3 border-t pt-4">
            <label
                class="flex items-center gap-2 text-sm"
                for="reactions-free-text"
            >
                <Checkbox
                    id="reactions-free-text"
                    bind:checked={freeText}
                    data-test="reactions-free-text"
                />
                <span>
                    Allow free-text messages
                    <span class="block text-xs text-muted-foreground">
                        The audience can send a short message to the screen.
                    </span>
                </span>
            </label>

            {#if freeText}
                <Input
                    label="Max length (up to {MAX_FREE_TEXT_LENGTH})"
                    id="reactions-max-length"
                    fieldsetClass="max-w-[16rem]"
                    type="number"
                    min="1"
                    max={MAX_FREE_TEXT_LENGTH}
                    bind:value={maxLength}
                    onblur={clampMaxLength}
                    data-test="reactions-max-length"
                />
            {/if}
        </div>
    </div>

    {#snippet actions()}
        <div class="flex w-full items-center justify-between gap-2">
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
        </div>
    {/snippet}
</Modal>
