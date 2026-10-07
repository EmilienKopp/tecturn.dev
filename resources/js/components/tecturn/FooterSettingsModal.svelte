<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ColorField from '@/components/tecturn/ColorField.svelte';
    import { Button, Checkbox, Input, Modal } from 'daisy-svelte';
    import type { FooterSettings } from '@/types/generated';
    import PresentFooter from './PresentFooter.svelte';

    let {
        footer,
        open = $bindable(false),
        onSave,
    }: {
        footer: FooterSettings;
        open?: boolean;
        onSave: (next: FooterSettings) => void;
    } = $props();

    const userDefaults = $derived(page.props.auth?.user);

    // Staged edits — only committed to the presentation on Save.
    let enabled = $state(false);
    let xHandle = $state('');
    let githubHandle = $state('');
    let hashtag = $state('');
    let bgColor = $state('#000000');
    let transparent = $state(true);
    let fontColor = $state('#ffffff');
    let showInDock = $state(false);

    const stripLeading = (value: string | null | undefined): string =>
        (value ?? '').replace(/^[@#]/, '').trim();

    // Seed the form from the saved footer value first, then the user's profile
    // handle as a default when the footer field is empty.
    function seed() {
        enabled = footer.enabled;
        xHandle =
            stripLeading(footer.xHandle) ||
            stripLeading(userDefaults?.social_x_handle);
        githubHandle =
            stripLeading(footer.githubHandle) ||
            stripLeading(userDefaults?.social_github_handle);
        hashtag = stripLeading(footer.hashtag);
        transparent = !footer.bgColor || footer.bgColor === 'transparent';
        bgColor = transparent ? '#000000' : footer.bgColor;
        fontColor = footer.fontColor || '#ffffff';
        showInDock = footer.showInDock;
    }

    // Re-seed whenever the modal transitions to open. The editor opens it by
    // setting `open` directly (bind:open), which bypasses onOpenChange, so we
    // watch the flag itself rather than relying on the Dialog callback.
    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            seed();
        }

        wasOpen = open;
    });

    const preview = $derived<FooterSettings>({
        enabled,
        xHandle: stripLeading(xHandle) || null,
        githubHandle: stripLeading(githubHandle) || null,
        hashtag: stripLeading(hashtag) || null,
        bgColor: transparent ? 'transparent' : bgColor,
        fontColor,
        showInDock,
    });

    function save() {
        onSave(preview);
        open = false;
    }
</script>

<Modal bind:open class="sm:max-w-lg">
    {#snippet title()}Footer{/snippet}
    <p class="text-muted-foreground text-sm">
        Shown across every slide while presenting.
    </p>

    <div class="mt-4 grid gap-4">
            <label class="flex items-center gap-2 text-sm" for="footer-enabled">
                <Checkbox
                    id="footer-enabled"
                    bind:checked={enabled}
                    data-test="footer-enabled"
                />
                Show footer
            </label>

            <Input
                label="X handle"
                id="footer-x"
                bind:value={xHandle}
                placeholder={stripLeading(userDefaults?.social_x_handle) ||
                    'yourhandle'}
                autocomplete="off"
                data-test="footer-x-handle"
            />

            <Input
                label="GitHub handle"
                id="footer-github"
                bind:value={githubHandle}
                placeholder={stripLeading(userDefaults?.social_github_handle) ||
                    'yourhandle'}
                autocomplete="off"
                data-test="footer-github-handle"
            />

            <Input
                label="Event hashtag"
                id="footer-hashtag"
                bind:value={hashtag}
                placeholder="myconf2026"
                autocomplete="off"
                data-test="footer-hashtag"
            />

            <div class="flex items-center gap-6">
                <div class="grid gap-2">
                    <label class="label" for="footer-bg">Background</label>
                    <div class="flex items-center gap-2">
                        <ColorField
                            id="footer-bg"
                            value={bgColor}
                            onchange={(color) => (bgColor = color)}
                            disabled={transparent}
                            class="h-9 w-12 cursor-pointer rounded border bg-transparent disabled:opacity-40"
                        />
                        <label
                            class="flex items-center gap-1.5 text-sm text-muted-foreground"
                            for="footer-transparent"
                        >
                            <Checkbox
                                id="footer-transparent"
                                bind:checked={transparent}
                                data-test="footer-transparent"
                            />
                            Transparent
                        </label>
                    </div>
                </div>

                <div class="grid gap-2">
                    <label class="label" for="footer-font">Font color</label>
                    <ColorField
                        id="footer-font"
                        value={fontColor}
                        onchange={(color) => (fontColor = color)}
                        class="h-9 w-12 cursor-pointer rounded border bg-transparent"
                    />
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm" for="footer-in-dock">
                <Checkbox
                    id="footer-in-dock"
                    bind:checked={showInDock}
                    data-test="footer-in-dock"
                />
                <span>
                    Show in dock
                    <span class="block text-xs text-muted-foreground">
                        Only visible when the dock is turned on.
                    </span>
                </span>
            </label>

            <div class="grid gap-2">
                <span class="text-xs font-medium text-muted-foreground"
                    >Preview</span
                >
                <div
                    class="relative flex h-16 items-end overflow-hidden rounded border bg-[repeating-conic-gradient(#e5e7eb_0_25%,#f9fafb_0_50%)] bg-[length:16px_16px]"
                >
                    <PresentFooter footer={preview} variant="overlay" />
                </div>
            </div>
        </div>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}
            >Cancel</Button
        >
        <Button onclick={save} data-test="footer-save">Save</Button>
    {/snippet}
</Modal>
