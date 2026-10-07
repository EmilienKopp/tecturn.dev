<script lang="ts">
    import { router, page } from '@inertiajs/svelte';
    import { CurlyBraces } from 'lucide-svelte';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import CodeXml from 'lucide-svelte/icons/code-xml';
    import Copy from 'lucide-svelte/icons/copy';
    import Download from 'lucide-svelte/icons/download';
    import FlaskConical from 'lucide-svelte/icons/flask-conical';
    import GitBranch from 'lucide-svelte/icons/git-branch';
    import Heart from 'lucide-svelte/icons/heart';
    import Languages from 'lucide-svelte/icons/languages';
    import Lock from 'lucide-svelte/icons/lock';
    import PanelBottom from 'lucide-svelte/icons/panel-bottom';
    import PanelRight from 'lucide-svelte/icons/panel-right';
    import Play from 'lucide-svelte/icons/play';
    import Plus from 'lucide-svelte/icons/plus';
    import QrCode from 'lucide-svelte/icons/qr-code';
    import Save from 'lucide-svelte/icons/save';
    import Settings2 from 'lucide-svelte/icons/settings-2';
    import Smartphone from 'lucide-svelte/icons/smartphone';
    import Timer from 'lucide-svelte/icons/timer';
    import Confirm from '@/components/feedback/Confirm.svelte';
    import { promise } from '@/lib/support/async';
    import { ms } from '@/lib/support/time';
    import {
        countUnits,
        slideProseText,
    } from '@/lib/tecturn/CodeGeneration/lint';
    import { qrToSvg } from '@/lib/tecturn/CodeGeneration/qr';
    import type { EditorState } from '@/lib/tecturn/editor-state.svelte';
    import { DEFAULT_REACTIONS } from '@/lib/tecturn/reactions';
    import { edit, present, update } from '@/routes/presentations';
    import { store as storeVersion } from '@/routes/presentations/versions';
    import type {
        DeliveryStats,
        FooterSettings,
        TalkSettings,
    } from '@/types/generated';
    import { Button, Checkbox, Dropdown, Modal, toast } from 'daisy-svelte';
    import FooterSettingsModal from './FooterSettingsModal.svelte';
    import ReactionsSettingsModal from './ReactionsSettingsModal.svelte';
    import TalkLengthModal from './TalkLengthModal.svelte';

    let {
        editor,
        presentationId,
        talkSettings,
        isPrivate,
        version = null,
        versions = [],
        external = false,
        name = $bindable(),
        view = $bindable(),
        onExport,
        onExportWebComponent,
        onExportJSON,
        embedSnippet,
        viewerUrl,
        remoteUrl,
        deliveryStats,
    }: {
        editor: EditorState;
        presentationId: number;
        talkSettings: TalkSettings;
        isPrivate: boolean;
        // "2.1"-style label, or null for decks that never joined a talk.
        version?: string | null;
        // Every version of the deck's talk, newest first.
        versions?: {
            id: number;
            version: string | null;
            name: string;
            updated_at: string | null;
            current: boolean;
        }[];
        // External decks bring their own slides (PDF / Google Slides), so the
        // view toggle, auto-save and export make no sense for them.
        external?: boolean;
        name: string;
        view: 'slides' | 'flow';
        onExport: () => void;
        onExportWebComponent: () => Promise<void>;
        onExportJSON: () => Promise<void>;
        embedSnippet: string;
        viewerUrl: string;
        // Pairing URL for the phone remote, popped up as a QR from here (and
        // only here — the editor is never projected, unlike the present view).
        remoteUrl: string;
        // Measured history (rehearsals + finished live sessions), shown in the
        // talk-length dialog next to the linter's estimate.
        deliveryStats: DeliveryStats;
    } = $props();

    let showReactions = $state(talkSettings.showReactions);
    let reactions = $state<string[]>(
        talkSettings.reactions?.length
            ? [...talkSettings.reactions]
            : [...DEFAULT_REACTIONS],
    );
    let allowFreeText = $state(talkSettings.allowFreeText ?? false);
    let freeTextMaxLength = $state(talkSettings.freeTextMaxLength ?? 20);
    let reactionsModalOpen = $state(false);
    let presentationPrivate = $state(isPrivate);
    let showDock = $state(talkSettings.showDock);
    let showTranslation = $state(talkSettings.showTranslation);
    let savingTalkSettings = $state(false);
    let autoSave = $state(talkSettings.autoSave ?? false);
    let footer = $state<FooterSettings>(talkSettings.footer);
    let footerModalOpen = $state(false);
    let durationMinutes = $state<number | null>(talkSettings.durationMinutes);
    let timerMode = $state(talkSettings.timerMode);
    let presentationStyle = $state(
        talkSettings.presentationStyle ?? 'balanced',
    );
    let talkLengthModalOpen = $state(false);
    let saving = $state(promise());
    let confirmModal: Confirm;
    let creatingVersion = $state(false);

    // Duplicates the deck as the next major/minor version of its talk and
    // lands in the new copy's editor. The first bump also creates the talk
    // and stamps this deck as 1.0.
    const switchVersion = (deckId: number): void => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam || deckId === presentationId) {
            return;
        }

        router.visit(
            edit({
                current_team: currentTeam.slug,
                presentation: deckId,
            }).url,
        );
    };

    const createVersion = (bump: 'major' | 'minor'): void => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam || creatingVersion) {
            return;
        }

        creatingVersion = true;
        router.post(
            storeVersion({
                current_team: currentTeam.slug,
                presentation: presentationId,
            }).url,
            { bump },
            {
                onFinish: () => {
                    creatingVersion = false;
                },
            },
        );
    };

    const AUTO_SAVE_INTERVAL = ms(10_000);

    // Word total of the current deck, for the measured words-per-minute figure
    // in the talk-length dialog (measured time ÷ today's text).
    const deckWordCount = $derived(
        editor.content.slides.reduce(
            (sum, slide) => sum + countUnits(slideProseText(slide)).words,
            0,
        ),
    );

    const persistTalkSettings = (
        apply: () => void,
        rollback: () => void,
    ): void => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam || savingTalkSettings) {
            return;
        }

        apply();
        savingTalkSettings = true;

        router.put(
            update({
                current_team: currentTeam.slug,
                presentation: presentationId,
            }).url,
            {
                talk_settings: {
                    ...talkSettings,
                    showReactions,
                    reactions,
                    allowFreeText,
                    freeTextMaxLength,
                    showDock,
                    showTranslation,
                    autoSave,
                    footer,
                    durationMinutes,
                    timerMode,
                    presentationStyle,
                },
            },
            {
                preserveState: true,
                onError: rollback,
                onFinish: () => {
                    savingTalkSettings = false;
                },
            },
        );
    };

    const saveReactions = (next: {
        showReactions: boolean;
        reactions: string[];
        allowFreeText: boolean;
        freeTextMaxLength: number;
    }) => {
        const previousShow = showReactions;
        const previousReactions = reactions;
        const previousAllowFreeText = allowFreeText;
        const previousMaxLength = freeTextMaxLength;
        persistTalkSettings(
            () => {
                showReactions = next.showReactions;
                reactions = next.reactions;
                allowFreeText = next.allowFreeText;
                freeTextMaxLength = next.freeTextMaxLength;
            },
            () => {
                showReactions = previousShow;
                reactions = previousReactions;
                allowFreeText = previousAllowFreeText;
                freeTextMaxLength = previousMaxLength;
            },
        );
    };

    const toggleDock = () =>
        persistTalkSettings(
            () => (showDock = !showDock),
            () => (showDock = !showDock),
        );

    const toggleTranslation = () =>
        persistTalkSettings(
            () => (showTranslation = !showTranslation),
            () => (showTranslation = !showTranslation),
        );

    const toggleAutoSave = () => {
        persistTalkSettings(
            () => (autoSave = !autoSave),
            () => (autoSave = !autoSave),
        );
    };

    const togglePrivacy = () => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        const next = !presentationPrivate;
        presentationPrivate = next;

        router.put(
            update({
                current_team: currentTeam.slug,
                presentation: presentationId,
            }).url,
            {
                is_private: next,
            },
            {
                preserveState: true,
                onError: () => {
                    presentationPrivate = !next;
                },
            },
        );
    };

    const saveFooter = (next: FooterSettings) => {
        const previous = footer;
        persistTalkSettings(
            () => (footer = next),
            () => (footer = previous),
        );
    };

    const saveTalkLength = (next: {
        durationMinutes: number | null;
        timerMode: string;
        presentationStyle: string;
    }) => {
        const previousDuration = durationMinutes;
        const previousMode = timerMode;
        const previousStyle = presentationStyle;
        persistTalkSettings(
            () => {
                durationMinutes = next.durationMinutes;
                timerMode = next.timerMode;
                presentationStyle = next.presentationStyle;
            },
            () => {
                durationMinutes = previousDuration;
                timerMode = previousMode;
                presentationStyle = previousStyle;
            },
        );
    };

    const copyEmbedSnippet = async () => {
        const copy = async () => {
            await navigator.clipboard.writeText(embedSnippet);
            toast.success('Embed code copied to clipboard.');
        };

        if (showDock) {
            confirmModal?.confirm({
                title: 'Dock will not be included in an embed snippet.',
                text: 'Are you sure you want to continue without the dock?',
                onConfirm: () => {
                    copy();
                },
            });
        } else {
            copy();
        }
    };

    const copyViewerUrl = async () => {
        await navigator.clipboard.writeText(viewerUrl);
        toast.success('Reaction URL copied to clipboard.');
    };

    // --- Phone remote pairing ---
    let remoteModalOpen = $state(false);
    const remoteQrSvg = $derived(
        remoteModalOpen
            ? qrToSvg(remoteUrl, { title: 'Phone remote pairing code' })
            : null,
    );

    const copyRemoteUrl = async () => {
        await navigator.clipboard.writeText(remoteUrl);
        toast.success('Remote URL copied to clipboard.');
    };

    let exportingWebComponent = $state(false);

    const exportWebComponent = async () => {
        exportingWebComponent = true;

        try {
            await onExportWebComponent();
        } finally {
            exportingWebComponent = false;
        }
    };

    const presentUrl = $derived(
        page.props.currentTeam
            ? present({
                  current_team: page.props.currentTeam.slug,
                  presentation: presentationId,
              }).url
            : null,
    );

    // Same present screen, but `test=1` tells the presenter to skip opening an
    // analytics session so a rehearsal never pollutes the numbers.
    const testRunUrl = $derived(presentUrl ? `${presentUrl}?test=1` : null);

    // Rehearsal mode: same screen again, but with a start/stop rehearsal timer
    // whose runs are saved with a snapshot of the deck.
    const rehearsalUrl = $derived(
        presentUrl ? `${presentUrl}?rehearsal=1` : null,
    );

    const save = async () => {
        const currentTeam = page.props.currentTeam;

        if (!currentTeam) {
            return;
        }

        saving = new Promise<void>((resolve) => {
            router.put(
                update({
                    current_team: currentTeam.slug,
                    presentation: presentationId,
                }).url,
                {
                    name,
                    content: editor.content,
                    flow: $state.snapshot(editor.flow),
                    is_private: presentationPrivate,
                    autoSave: autoSave,
                },
                {
                    preserveState: true,
                    onSuccess: () => {
                        editor.dirty = false;
                    },
                    onFinish: () => {
                        resolve();
                    },
                },
            );
        });
    };

    setInterval(() => {
        if (autoSave && editor.dirty) {
            save();
        }
    }, AUTO_SAVE_INTERVAL.milliseconds());
</script>

<div class="flex items-center gap-3 border-b px-4 py-2">
    <input
        type="text"
        class="input max-w-xs font-medium"
        bind:value={name}
        oninput={() => (editor.dirty = true)}
        data-test="editor-presentation-name"
    />

    {#if !external}
        <div
            class="text-sm flex items-center gap-1 justify-center"
            title="Toggle auto save (every {AUTO_SAVE_INTERVAL.seconds()}s)"
            class:text-muted-foreground={!autoSave}
        >
            <Checkbox
                id="auto-save-toggle"
                class="checkbox-sm text-muted-foreground"
                data-test="editor-toggle-auto-save"
                onclick={toggleAutoSave}
                checked={autoSave}
            />
            <label class="label" for="auto-save-toggle">Auto Save</label>
        </div>
    {/if}

    {#snippet toggleRow(
        label: string,
        Icon: typeof Heart,
        checked: boolean,
        toggle: () => void,
        testId: string,
    )}
        <button
            type="button"
            role="menuitemcheckbox"
            aria-checked={checked}
            class="flex w-full cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
            disabled={savingTalkSettings}
            onclick={toggle}
            data-test={testId}
        >
            <Icon class="h-4 w-4" />
            {label}
            <span
                class="ml-auto text-xs {checked
                    ? 'font-medium text-primary'
                    : 'text-muted-foreground'}"
            >
                {checked ? 'On' : 'Off'}
            </span>
        </button>
    {/snippet}

    <div class="ml-auto flex items-center gap-2">
        <Button
            variant="ghost"
            size="sm"
            class="text-muted-foreground"
            onclick={copyViewerUrl}
            title="Copy the URL the audience uses to react"
            data-test="editor-copy-viewer-url"
        >
            <QrCode class="h-4 w-4" /> Reaction URL
        </Button>

        <Button
            variant="ghost"
            size="sm"
            class="text-muted-foreground"
            onclick={() => (remoteModalOpen = true)}
            title="Pair your phone as a remote with speaker notes"
            data-test="editor-remote-button"
        >
            <Smartphone class="h-4 w-4" /> Remote
        </Button>

        {#if presentUrl}
            <Dropdown align="end" class="w-56">
                {#snippet trigger()}
                    <span
                        class="btn btn-primary btn-sm shadow"
                        data-test="editor-present-menu"
                    >
                        <Play class="h-4 w-4" /> Present
                        <ChevronDown class="h-3.5 w-3.5 opacity-60" />
                    </span>
                {/snippet}
                {#snippet children({ close })}
                    <li>
                        <a
                            class="gap-2"
                            onclick={close}
                            href={presentUrl}
                            target="_blank"
                            rel="noopener"
                            data-test="editor-present-link"
                        >
                            <Play class="h-4 w-4" />Go Live
                        </a>
                    </li>
                    <li>
                        <a
                            class="gap-2"
                            onclick={close}
                            href={testRunUrl}
                            target="_blank"
                            rel="noopener"
                            data-test="editor-test-run-link"
                        >
                            <FlaskConical class="h-4 w-4" />Test run
                        </a>
                    </li>
                    <li>
                        <a
                            class="gap-2"
                            onclick={close}
                            href={rehearsalUrl}
                            target="_blank"
                            rel="noopener"
                            data-test="editor-rehearse-link"
                        >
                            <Timer class="h-4 w-4" />Rehearse
                        </a>
                    </li>
                {/snippet}
            </Dropdown>
        {/if}

        <Dropdown align="end" class="w-64">
            {#snippet trigger()}
                <span
                    class="btn btn-outline btn-sm"
                    data-test="editor-versions-menu"
                >
                    <GitBranch class="h-4 w-4" />
                    <span class="font-mono text-xs"
                        >{version ? `v${version}` : 'Versions'}</span
                    >
                    <ChevronDown class="h-3.5 w-3.5 opacity-60" />
                </span>
            {/snippet}
            {#snippet children({ close })}
                {#each versions as deck (deck.id)}
                    <li>
                        <button
                            type="button"
                            class="gap-2 {deck.current
                                ? 'font-medium text-primary'
                                : ''}"
                            onclick={() => {
                                close();
                                switchVersion(deck.id);
                            }}
                            data-test="editor-version-row"
                        >
                            <span class="font-mono text-xs"
                                >{deck.version ? `v${deck.version}` : '—'}</span
                            >
                            <span class="truncate">{deck.name}</span>
                            {#if deck.current}
                                <span
                                    class="ml-auto text-[10px] tracking-wide uppercase"
                                >
                                    current
                                </span>
                            {/if}
                        </button>
                    </li>
                {/each}
                {#if versions.length > 0}
                    <li class="border-base-300 my-1 border-t"></li>
                {/if}
                <li>
                    <button
                        type="button"
                        class="gap-2"
                        disabled={creatingVersion}
                        onclick={() => {
                            close();
                            createVersion('minor');
                        }}
                        data-test="editor-new-minor-version-menu-item"
                    >
                        <Plus class="h-4 w-4" />
                        New minor version
                    </button>
                </li>
                <li>
                    <button
                        type="button"
                        class="gap-2"
                        disabled={creatingVersion}
                        onclick={() => {
                            close();
                            createVersion('major');
                        }}
                        data-test="editor-new-major-version-menu-item"
                    >
                        <Plus class="h-4 w-4" />
                        New major version
                    </button>
                </li>
            {/snippet}
        </Dropdown>

        <Dropdown align="end" class="w-56">
            {#snippet trigger()}
                <span
                    class="btn btn-outline btn-sm"
                    data-test="editor-settings-menu"
                >
                    <Settings2 class="h-4 w-4" /> Settings
                    <ChevronDown class="h-3.5 w-3.5 opacity-60" />
                </span>
            {/snippet}
            {#snippet children({ close })}
                <li>
                    <button
                        type="button"
                        class="gap-2"
                        onclick={() => {
                            close();
                            reactionsModalOpen = true;
                        }}
                        data-test="editor-reactions-menu-item"
                    >
                        <Heart class="h-4 w-4" />
                        Reactions…
                        <span
                            class="ml-auto text-xs {showReactions
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'}"
                        >
                            {showReactions ? 'On' : 'Off'}
                        </span>
                    </button>
                </li>
                <li>
                    {@render toggleRow(
                        'Dock',
                        PanelRight,
                        showDock,
                        toggleDock,
                        'editor-dock-toggle',
                    )}
                </li>
                <li>
                    {@render toggleRow(
                        'Live Translation',
                        Languages,
                        showTranslation,
                        toggleTranslation,
                        'editor-translation-toggle',
                    )}
                </li>
                <li>
                    {@render toggleRow(
                        'Private talk',
                        Lock,
                        presentationPrivate,
                        togglePrivacy,
                        'editor-private-toggle',
                    )}
                </li>
                <li>
                    <button
                        type="button"
                        class="gap-2"
                        onclick={() => {
                            close();
                            talkLengthModalOpen = true;
                        }}
                        data-test="editor-talk-length-menu-item"
                    >
                        <Timer class="h-4 w-4" />
                        Talk length…
                        <span
                            class="ml-auto text-xs {durationMinutes
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'}"
                        >
                            {durationMinutes ? `${durationMinutes}m` : 'Off'}
                        </span>
                    </button>
                </li>
                <li>
                    <button
                        type="button"
                        class="gap-2"
                        onclick={() => {
                            close();
                            footerModalOpen = true;
                        }}
                        data-test="editor-footer-menu-item"
                    >
                        <PanelBottom class="h-4 w-4" />
                        Footer…
                        <span
                            class="ml-auto text-xs {footer.enabled
                                ? 'font-medium text-primary'
                                : 'text-muted-foreground'}"
                        >
                            {footer.enabled ? 'On' : 'Off'}
                        </span>
                    </button>
                </li>
            {/snippet}
        </Dropdown>

        {#if !external}
            <Dropdown align="end" class="w-60">
                {#snippet trigger()}
                    <span
                        class="btn btn-outline btn-sm"
                        data-test="editor-export-menu"
                    >
                        <Download class="h-4 w-4" /> Export
                        <ChevronDown class="h-3.5 w-3.5 opacity-60" />
                    </span>
                {/snippet}
                {#snippet children({ close })}
                    <li>
                        <button
                            type="button"
                            onclick={() => {
                                close();
                                onExport();
                            }}
                            data-test="editor-export-button"
                        >
                            <Download class="mr-2 h-4 w-4" /> Export Svelte
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            disabled={exportingWebComponent}
                            onclick={() => {
                                close();
                                exportWebComponent();
                            }}
                            data-test="editor-export-web-component-button"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            {exportingWebComponent
                                ? 'Exporting…'
                                : 'Export Web Component'}
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            onclick={() => {
                                close();
                                onExportJSON();
                            }}
                            data-test="editor-export-json-button"
                        >
                            <CurlyBraces class="mr-2 h-4 w-4" /> Export JSON
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            onclick={() => {
                                close();
                                copyEmbedSnippet();
                            }}
                            data-test="editor-copy-embed-button"
                        >
                            <CodeXml class="mr-2 h-4 w-4" /> Copy Embed
                        </button>
                    </li>
                {/snippet}
            </Dropdown>
        {/if}

        <Button
            size="sm"
            disabled={!editor.dirty}
            onclick={save}
            data-test="editor-save-button"
        >
            <Save class="h-4 w-4" />
            {#key saving}
                {#await saving}
                    Saving…
                {:then}
                    Save
                {/await}
            {/key}
        </Button>
    </div>
</div>

<Confirm bind:this={confirmModal} />

<FooterSettingsModal {footer} bind:open={footerModalOpen} onSave={saveFooter} />

<ReactionsSettingsModal
    {reactions}
    {showReactions}
    {allowFreeText}
    {freeTextMaxLength}
    bind:open={reactionsModalOpen}
    onSave={saveReactions}
/>

<TalkLengthModal
    {durationMinutes}
    {timerMode}
    {presentationStyle}
    {deliveryStats}
    deckWords={deckWordCount}
    bind:open={talkLengthModalOpen}
    onSave={saveTalkLength}
/>

<!-- Phone-remote pairing QR. Deliberately editor-only: this screen is not the
     one on the projector, so the control credential never reaches the room. -->
<Modal bind:open={remoteModalOpen} class="sm:max-w-md">
    {#snippet title()}Phone remote{/snippet}
    <div class="space-y-3">
        <p class="text-muted-foreground text-sm">
            Scan with your phone to get slide controls, quiz buzzers and your
            speaker notes. Pair here, then press Go Live — anyone who sees this
            code can drive your deck.
        </p>
        {#if remoteQrSvg}
            <div
                class="mx-auto w-64 max-w-full rounded-lg bg-white p-3"
                data-test="editor-remote-qr"
            >
                <!-- eslint-disable-next-line svelte/no-at-html-tags -->
                {@html remoteQrSvg}
            </div>
        {/if}
        <div class="flex items-center gap-2">
            <code
                class="min-w-0 flex-1 truncate rounded-md border bg-muted px-2.5 py-1.5 text-xs text-muted-foreground"
                title={remoteUrl}
                data-test="editor-remote-url"
            >
                {remoteUrl}
            </code>
            <Button
                variant="base"
                outline
                size="sm"
                onclick={copyRemoteUrl}
                title="Copy the remote URL"
                data-test="editor-remote-copy-url"
            >
                <Copy class="h-4 w-4" /> Copy
            </Button>
        </div>
    </div>
</Modal>
