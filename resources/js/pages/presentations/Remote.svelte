<script lang="ts">
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import { onMount } from 'svelte';
    import AppHead from '@/components/AppHead.svelte';
    import { getEcho, setControlToken, setPresenceIdentity } from '@/lib/echo';
    import {
        defaultFlowFromContent,
        enabledSlideIds,
        migrateLegacyTransitions,
    } from '@/lib/tecturn/flow-compiler';
    import type { FlowGraph, PresentationContent } from '@/types/generated';

    let {
        presentationName,
        embedToken,
        remoteToken,
        sourceType,
        content,
        flow = null,
    }: {
        presentationName: string;
        embedToken: string;
        remoteToken: string;
        sourceType: string;
        content: PresentationContent;
        flow?: FlowGraph | null;
    } = $props();

    // Same shown-slide pipeline as the Presenter, so index N here is the same
    // slide as index N on the big screen and the notes line up. Snapshots
    // because the migration structuredClones its inputs, which rejects $state
    // proxies (same guard as Presenter.svelte).
    const shownSlides = $derived.by(() => {
        if (sourceType !== 'editor') {
            return [];
        }

        const contentSnapshot = $state.snapshot(content);
        const flowSnapshot = $state.snapshot(flow);
        const compiled = migrateLegacyTransitions(
            contentSnapshot,
            flowSnapshot ?? defaultFlowFromContent(contentSnapshot),
        );
        const enabled = enabledSlideIds(compiled.content, compiled.flow);

        return compiled.content.slides.filter((slide) =>
            enabled.has(slide.id),
        );
    });

    // Position mirrored from the presenter's state whispers. Until the first
    // one lands we're pairing, not driving.
    let connected = $state(false);
    let currentSlide = $state(0);
    let slideCount = $state(0);

    const activeSlide = $derived(shownSlides[currentSlide] ?? null);

    type ControlChannel = {
        whisper: (event: string, data: unknown) => unknown;
        listenForWhisper: (
            event: string,
            callback: (data: never) => void,
        ) => ControlChannel;
        subscribed: (callback: () => void) => ControlChannel;
    };

    let channel: ControlChannel | null = null;

    const send = (action: 'next' | 'prev' | 'buzz' | 'ding'): void => {
        channel?.whisper('command', { action });

        if (navigator.vibrate) {
            navigator.vibrate(10);
        }
    };

    onMount(() => {
        const channelName = `presentation-control.${embedToken}`;

        setPresenceIdentity(`remote:${crypto.randomUUID()}`);
        setControlToken(remoteToken);

        channel = (
            getEcho().private(channelName) as unknown as ControlChannel
        )
            .listenForWhisper(
                'state',
                (event: { slide?: number; total?: number }): void => {
                    connected = true;
                    currentSlide = event.slide ?? 0;
                    slideCount = event.total ?? 0;
                },
            )
            // Ask the presenter where it is; it answers with a state whisper.
            .subscribed(() => channel?.whisper('hello', {}));

        // The phone may pair before the presenter screen opens (the intended
        // flow: scan in the editor, then press Go Live). Keep knocking until
        // a state whisper answers.
        const retry = setInterval(() => {
            if (!connected) {
                channel?.whisper('hello', {});
            }
        }, 3000);

        return () => {
            clearInterval(retry);
            channel = null;
            getEcho().leave(channelName);
        };
    });
</script>

<AppHead title="Remote · {presentationName}" />

<main
    class="flex h-dvh flex-col px-5 pt-[max(1.25rem,env(safe-area-inset-top))] pb-[max(1.25rem,env(safe-area-inset-bottom))]"
>
    <header class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p
                class="flex items-center gap-2 font-mono text-[11px] font-semibold tracking-[0.3em] text-[hsl(37_6%_55%)] uppercase"
            >
                <span
                    class="live-dot h-2 w-2 rounded-full {connected
                        ? 'bg-[hsl(37_91%_55%)]'
                        : 'bg-[hsl(37_6%_40%)]'}"
                    aria-hidden="true"
                ></span>
                {connected ? 'Remote' : 'Waiting for the presenter…'}
            </p>
            <h1 class="mt-1 truncate text-sm font-semibold text-white">
                {presentationName}
            </h1>
        </div>
        {#if slideCount > 0}
            <p
                class="shrink-0 font-mono text-sm tabular-nums text-[hsl(37_6%_55%)]"
                data-test="remote-slide-indicator"
            >
                {currentSlide + 1} / {slideCount}
            </p>
        {/if}
    </header>

    <!-- Speaker notes fill the middle of the screen. -->
    <section
        class="footlight-panel mt-4 min-h-0 flex-1 overflow-y-auto rounded-2xl p-4"
        aria-live="polite"
    >
        {#if activeSlide?.title}
            <p
                class="mb-2 text-xs font-semibold tracking-wider text-[hsl(37_91%_55%)] uppercase"
            >
                {activeSlide.title}
            </p>
        {/if}
        {#if activeSlide?.notes}
            <p
                class="text-lg leading-relaxed whitespace-pre-wrap text-[hsl(40_20%_88%)]"
                data-test="remote-notes"
            >
                {activeSlide.notes}
            </p>
        {:else}
            <p class="text-sm text-[hsl(37_6%_55%)]">
                {sourceType === 'editor'
                    ? 'No speaker notes for this slide.'
                    : 'Speaker notes are available for editor decks.'}
            </p>
        {/if}
    </section>

    <!-- Navigation: two big thumb targets. -->
    <div class="mt-4 grid grid-cols-2 gap-3">
        <button
            type="button"
            class="footlight-key flex h-24 items-center justify-center rounded-2xl text-white select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[hsl(37_91%_55%)]"
            onclick={() => send('prev')}
            aria-label="Previous step"
            data-test="remote-prev"
        >
            <ChevronLeft class="h-10 w-10" />
        </button>
        <button
            type="button"
            class="footlight-key flex h-24 items-center justify-center rounded-2xl text-[hsl(37_91%_55%)] select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[hsl(37_91%_55%)]"
            onclick={() => send('next')}
            aria-label="Next step"
            data-test="remote-next"
        >
            <ChevronRight class="h-10 w-10" />
        </button>
    </div>

    <!-- Quiz buzzers: they sound on the presenting device, not the phone. -->
    <div class="mt-3 grid grid-cols-2 gap-3">
        <button
            type="button"
            class="footlight-key flex h-14 items-center justify-center gap-2 rounded-2xl text-sm font-semibold text-red-400 select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400"
            onclick={() => send('buzz')}
            data-test="remote-buzz"
        >
            ✕ Buzz
        </button>
        <button
            type="button"
            class="footlight-key flex h-14 items-center justify-center gap-2 rounded-2xl text-sm font-semibold text-emerald-400 select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-400"
            onclick={() => send('ding')}
            data-test="remote-ding"
        >
            ✓ Ding
        </button>
    </div>
</main>

<style>
    .footlight-panel {
        background-color: hsl(34 10% 13%);
        box-shadow:
            inset 0 1px 0 hsl(40 20% 92% / 0.05),
            0 0 0 1px hsl(34 9% 19%);
    }

    .footlight-key {
        background-color: hsl(34 10% 15%);
        box-shadow:
            inset 0 1px 0 hsl(40 20% 92% / 0.06),
            0 0 0 1px hsl(34 9% 21%),
            0 8px 24px -12px hsl(36 45% 4% / 0.8);
        transition:
            transform 120ms ease,
            box-shadow 200ms ease;
    }

    .footlight-key:active {
        transform: scale(0.95);
    }

    @keyframes live-pulse {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.35;
        }
    }

    .live-dot {
        animation: live-pulse 2.4s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .live-dot {
            animation: none;
        }

        .footlight-key,
        .footlight-key:active {
            transition: none;
            transform: none;
        }
    }
</style>
