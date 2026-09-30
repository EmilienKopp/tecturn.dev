<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import { SvelteSet } from 'svelte/reactivity';
    import AppHead from '@/components/AppHead.svelte';
    import ExternalPresenter from '@/components/tecturn/ExternalPresenter.svelte';
    import FloatingReactions from '@/components/tecturn/FloatingReactions.svelte';
    import Presenter from '@/components/tecturn/Presenter.svelte';
    import PresenterDock from '@/components/tecturn/PresenterDock.svelte';
    import PresentFooter from '@/components/tecturn/PresentFooter.svelte';
    import type { RehearsalPayload } from '@/components/tecturn/RehearsalDock.svelte';
    import RehearsalDock from '@/components/tecturn/RehearsalDock.svelte';
    import YoYoTranslatePanel from '@/components/tecturn/YoYoTranslatePanel.svelte';
    import { getEcho, setControlToken, setPresenceIdentity } from '@/lib/echo';
    import { beaconPost } from '@/lib/tecturn/beacon';
    import { playBuzzer, unlockBuzzerAudio } from '@/lib/tecturn/buzzer';
    import type {
        FlowGraph,
        PresentationContent,
        PresentationSource,
        TalkSettings,
        YoYoTranslateInfo,
    } from '@/types/generated';

    let {
        presentation,
        sourcePdfUrl = null,
        viewerUrl,
        remote,
        sessionRoutes,
        translationRoutes,
        testMode = false,
        rehearsalMode = false,
        rehearsalRoutes,
    }: {
        presentation: {
            id: number;
            name: string;
            content: PresentationContent;
            talk_settings: TalkSettings;
            flow: FlowGraph | null;
            source: PresentationSource;
            embed_token: string;
            updated_at: string | null;
            yoyotranslate: YoYoTranslateInfo;
        };
        sourcePdfUrl?: string | null;
        viewerUrl: string;
        // The deck's remote-control secret, used to join the control channel.
        // The pairing QR itself lives in the editor toolbar, never here.
        remote: { token: string };
        sessionRoutes: { start: string; close: string };
        translationRoutes: { start: string; stop: string };
        testMode?: boolean;
        rehearsalMode?: boolean;
        rehearsalRoutes?: { store: string };
    } = $props();

    // A finished rehearsal posts its timings; the backend snapshots the deck
    // and redirects to the run's replay page.
    let savingRehearsal = $state(false);

    const saveRehearsal = (run: RehearsalPayload): void => {
        if (!rehearsalRoutes) {
            return;
        }

        savingRehearsal = true;
        router.post(rehearsalRoutes.store, run, {
            onFinish: () => {
                savingRehearsal = false;
            },
        });
    };

    // Current slide + shown-slide total, reported by the Presenter off Reveal,
    // so the dock can pace each slide against the talk target. The step index
    // (shown-fragment count) feeds the rehearsal dock's replay timeline.
    let currentSlide = $state(0);
    let currentStep = $state(0);
    // External decks carry a placeholder 1-slide content, so they start at 0 (no
    // counter) until the presenter reports a real total — a PDF's page count, or
    // a rehearsal's declared slide count. Editor decks count their own slides.
    let slideCount = $state(
        presentation.source.type === 'editor'
            ? presentation.content.slides.length
            : 0,
    );

    // Session-only overrides: start from the saved settings, toggled from the
    // dock without persisting. Messages are gated separately from emoji so the
    // presenter can block incoming text on the fly.
    let showReactions = $state(presentation.talk_settings.showReactions);
    let showMessages = $state(presentation.talk_settings.allowFreeText ?? false);

    let floatingReactions = $state<FloatingReactions>();
    let recentReactions = $state<{ id: number; emoji: string }[]>([]);
    let reactionCounter = 0;

    // A broadcast can reach us more than once (reconnects, duplicate channel
    // bindings); each carries a unique id so we handle it exactly once. Shared
    // across handlers/effect runs so duplicate bindings dedupe against it too.
    const seenEventIds = new SvelteSet<string>();
    const isDuplicateEvent = (id: string): boolean => {
        if (seenEventIds.has(id)) {
            return true;
        }

        seenEventIds.add(id);

        if (seenEventIds.size > 500) {
            seenEventIds.clear();
        }

        return false;
    };

    // Live stats shown in the dock, fed by the presentation broadcast channel.
    let viewerCount = $state(0);
    let reactionTotal = $state(0);

    // One identity for every channel this page joins, set before each
    // subscribe so the anonymous "viewer" guard always sees it.
    const presenterIdentity = `presenter:${crypto.randomUUID()}`;

    // Component handles so the phone remote can drive navigation.
    let presenterRef = $state<Presenter>();
    let externalRef = $state<ExternalPresenter>();

    // The joined control channel, kept so the state-whisper effect below can
    // publish without re-subscribing on every slide change.
    let controlChannel = $state<{
        whisper: (event: string, data: Record<string, unknown>) => unknown;
    } | null>(null);

    // Phone remote lane: nav commands and buzzer hits arrive as whispers, the
    // current position goes back out so the phone shows the right notes. Test
    // runs and rehearsals have no session (so no authorized token) — skip.
    $effect(() => {
        if (testMode || rehearsalMode) {
            return;
        }

        const controlChannelName = `presentation-control.${presentation.embed_token}`;

        setPresenceIdentity(presenterIdentity);
        setControlToken(remote.token);

        const channel = getEcho()
            .private(controlChannelName)
            .listenForWhisper(
                'command',
                (event: { action?: string }): void => {
                    if (event.action === 'next') {
                        presenterRef?.next();
                        externalRef?.remoteNext();
                    } else if (event.action === 'prev') {
                        presenterRef?.prev();
                        externalRef?.remotePrev();
                    } else if (
                        event.action === 'buzz' ||
                        event.action === 'ding'
                    ) {
                        playBuzzer(event.action);
                    }
                },
            )
            .listenForWhisper('hello', (): void => {
                channel.whisper('state', {
                    slide: currentSlide,
                    step: currentStep,
                    total: slideCount,
                });
            });

        controlChannel = channel;

        return () => {
            controlChannel = null;
            getEcho().leave(controlChannelName);
        };
    });

    // Rebroadcast the position whenever it moves, however it moved (keyboard,
    // clicker, or the phone itself), so the remote's notes stay in step.
    $effect(() => {
        controlChannel?.whisper('state', {
            slide: currentSlide,
            step: currentStep,
            total: slideCount,
        });
    });

    // Buzzer playback needs an unlocked AudioContext, which browsers only
    // grant inside a user gesture; the presenter's first interaction (a click
    // or an arrow key) quietly provides it.
    onMount(() => {
        const unlock = (): void => unlockBuzzerAudio();

        window.addEventListener('pointerdown', unlock, { once: true });
        window.addEventListener('keydown', unlock, { once: true });

        return () => {
            window.removeEventListener('pointerdown', unlock);
            window.removeEventListener('keydown', unlock);
        };
    });

    $effect(() => {
        const channelName = `presentation.${presentation.embed_token}`;
        const presenceChannel = `presentation-live.${presentation.embed_token}`;

        // Instant reactions and messages both ride the public channel. Stats
        // always update; the dock toggles only gate what floats on screen.
        getEcho()
            .channel(channelName)
            .listen('.reaction.sent', (event: { emoji: string; id: string }) => {
                if (isDuplicateEvent(event.id)) {
                    return;
                }

                if (showReactions) {
                    floatingReactions?.spawnReaction(event.emoji);
                }

                reactionTotal += 1;
                recentReactions = [
                    ...recentReactions,
                    { id: ++reactionCounter, emoji: event.emoji },
                ].slice(-30);
            })
            .listen(
                '.message.sent',
                (event: { message: string; id: string }) => {
                    if (isDuplicateEvent(event.id)) {
                        return;
                    }

                    if (showMessages) {
                        floatingReactions?.spawnMessage(event.message);
                    }
                },
            );

        // "Watching now" is the count of audience members on the presence
        // channel. Reverb adds/removes members as tabs open and close, so this
        // falls back to 0 on its own when the room empties. The presenter joins
        // as a "presenter" member and is excluded from the tally.
        // laravel-echo hands the member's user_info straight to these
        // callbacks, so `role` sits at the top level, not under `.info`.
        const isViewer = (member: { role?: string }): boolean =>
            member.role !== 'presenter';

        setPresenceIdentity(presenterIdentity);
        getEcho()
            .join(presenceChannel)
            .here((members: { role?: string }[]) => {
                viewerCount = members.filter(isViewer).length;
            })
            .joining((member: { role?: string }) => {
                if (isViewer(member)) {
                    viewerCount += 1;
                }
            })
            .leaving((member: { role?: string }) => {
                if (isViewer(member)) {
                    viewerCount = Math.max(0, viewerCount - 1);
                }
            });

        return () => {
            getEcho().leave(channelName);
            getEcho().leave(presenceChannel);
        };
    });

    // A live session opens while the presenter is on this page and closes when
    // they leave, so reactions and viewers are attributed to a real talk. A
    // test run or rehearsal skips this entirely: no session means the
    // backend records no analytics. Slides, presence and instant reactions
    // still work.
    onMount(() => {
        if (testMode || rehearsalMode) {
            return;
        }

        beaconPost(sessionRoutes.start);

        const close = (): void => beaconPost(sessionRoutes.close);

        window.addEventListener('pagehide', close);

        return () => {
            window.removeEventListener('pagehide', close);
            close();
        };
    });
</script>

<AppHead title={presentation.name} />

<div class="flex h-screen w-screen overflow-hidden bg-black">
    <!-- Slide column: a vertical stack so the caption bar docks above or
         below the slide area, shrinking it instead of overlapping it. The
         presenter dock keeps its full-height column to the right. -->
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
        <div
            class="relative flex min-h-0 flex-1 flex-col items-center justify-center overflow-hidden bg-black [container-type:size]"
        >
            <!-- The slide box is the largest 16:9 that fits the column on both
             axes, centered, so wide screens letterbox with black around it
             instead of the slide stretching to fill. -->
            <div
                style="width: min(100cqw, calc(100cqh * 16 / 9)); aspect-ratio: 16 / 9;"
            >
                {#if presentation.source.type === 'editor'}
                    <Presenter
                        bind:this={presenterRef}
                        content={presentation.content}
                        flow={presentation.flow}
                        onSlideChange={(current, total) => {
                            currentSlide = current;
                            slideCount = total;
                        }}
                        onStepChange={(_slide, step) => {
                            currentStep = step;
                        }}
                    />
                {:else}
                    <ExternalPresenter
                        bind:this={externalRef}
                        source={presentation.source}
                        {sourcePdfUrl}
                        recordNavigation={rehearsalMode}
                        onPageChange={(current, total) => {
                            currentSlide = current;
                            slideCount = total;
                        }}
                    />
                {/if}
            </div>

            <FloatingReactions bind:this={floatingReactions} enabled />
        </div>

        <!-- The footer is its own row beneath the slide area so it shrinks the
             slide instead of overlapping it. -->
        {#if presentation.talk_settings.footer.enabled && !presentation.talk_settings.footer.showInDock}
            <PresentFooter
                footer={presentation.talk_settings.footer}
                variant="overlay"
            />
        {/if}

        {#if presentation.talk_settings.showTranslation}
            <YoYoTranslatePanel
                yoyotranslate={presentation.yoyotranslate}
                routes={translationRoutes}
            />
        {/if}
    </div>

    <!-- Dock column. Rehearsal mode swaps the live dock for the rehearsal
         timer with its start/pause/stop controls, always visible. -->
    {#if rehearsalMode}
        <RehearsalDock
            talkSettings={presentation.talk_settings}
            {slideCount}
            {currentSlide}
            {currentStep}
            saving={savingRehearsal}
            onFinish={saveRehearsal}
        />
    {:else if presentation.talk_settings.showDock}
        <PresenterDock
            {viewerUrl}
            talkSettings={presentation.talk_settings}
            {slideCount}
            {currentSlide}
            {viewerCount}
            {reactionTotal}
            bind:showReactions
            bind:showMessages
        />
    {/if}
</div>
