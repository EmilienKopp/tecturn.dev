<script lang="ts">
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import FloatingReactions from '@/components/tecturn/FloatingReactions.svelte';
    import Presenter from '@/components/tecturn/Presenter.svelte';
    import PresenterDock from '@/components/tecturn/PresenterDock.svelte';
    import PresentFooter from '@/components/tecturn/PresentFooter.svelte';
    import YoYoTranslatePanel from '@/components/tecturn/YoYoTranslatePanel.svelte';
    import { getEcho, setPresenceIdentity } from '@/lib/echo';
    import { beaconPost } from '@/lib/tecturn/beacon';
    import type {
        FlowGraph,
        PresentationContent,
        TalkSettings,
        YoYoTranslateInfo,
    } from '@/types/generated';

    let {
        presentation,
        viewerUrl,
        sessionRoutes,
        translationRoutes,
        testMode = false,
    }: {
        presentation: {
            id: number;
            name: string;
            content: PresentationContent;
            talk_settings: TalkSettings;
            flow: FlowGraph | null;
            embed_token: string;
            updated_at: string | null;
            yoyotranslate: YoYoTranslateInfo;
        };
        viewerUrl: string;
        sessionRoutes: { start: string; close: string };
        translationRoutes: { start: string; stop: string };
        testMode?: boolean;
    } = $props();

    // Current slide + shown-slide total, reported by the Presenter off Reveal,
    // so the dock can pace each slide against the talk target.
    let currentSlide = $state(0);
    let slideCount = $state(presentation.content.slides.length);

    // Session-only override: starts from the saved setting, toggled from the
    // dock without persisting.
    let showReactions = $state(presentation.talk_settings.showReactions);

    let floatingReactions = $state<FloatingReactions>();
    let recentReactions = $state<{ id: number; emoji: string }[]>([]);
    let reactionCounter = 0;

    // Live stats shown in the dock, fed by the presentation broadcast channel.
    let viewerCount = $state(0);
    let reactionTotal = $state(0);

    $effect(() => {
        const channelName = `presentation.${presentation.embed_token}`;
        const presenceChannel = `presentation-live.${presentation.embed_token}`;

        // Instant reactions still ride the public channel.
        getEcho()
            .channel(channelName)
            .listen('.reaction.sent', (event: { emoji: string }) => {
                floatingReactions?.spawnReaction(event.emoji);
                reactionTotal += 1;
                recentReactions = [
                    ...recentReactions,
                    { id: ++reactionCounter, emoji: event.emoji },
                ].slice(-30);
            });

        // "Watching now" is the count of audience members on the presence
        // channel. Reverb adds/removes members as tabs open and close, so this
        // falls back to 0 on its own when the room empties. The presenter joins
        // as a "presenter" member and is excluded from the tally.
        // laravel-echo hands the member's user_info straight to these
        // callbacks, so `role` sits at the top level, not under `.info`.
        const isViewer = (member: { role?: string }): boolean =>
            member.role !== 'presenter';

        setPresenceIdentity(`presenter:${crypto.randomUUID()}`);
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
    // test run skips this entirely: no session means the backend records no
    // analytics. Slides, presence and instant reactions still work.
    onMount(() => {
        if (testMode) {
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
                <Presenter
                    content={presentation.content}
                    flow={presentation.flow}
                    onSlideChange={(current, total) => {
                        currentSlide = current;
                        slideCount = total;
                    }}
                />
            </div>

            <FloatingReactions
                bind:this={floatingReactions}
                enabled={showReactions}
            />

            {#if presentation.talk_settings.footer.enabled && !presentation.talk_settings.footer.showInDock}
                <PresentFooter
                    footer={presentation.talk_settings.footer}
                    variant="overlay"
                />
            {/if}
        </div>

        {#if presentation.talk_settings.showTranslation}
            <YoYoTranslatePanel
                yoyotranslate={presentation.yoyotranslate}
                routes={translationRoutes}
            />
        {/if}
    </div>

    <!-- Dock column -->
    {#if presentation.talk_settings.showDock}
        <PresenterDock
            {viewerUrl}
            talkSettings={presentation.talk_settings}
            {slideCount}
            {currentSlide}
            {recentReactions}
            {viewerCount}
            {reactionTotal}
            bind:showReactions
        />
    {/if}
</div>
