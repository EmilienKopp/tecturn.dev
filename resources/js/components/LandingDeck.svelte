<script lang="ts">
    import { Code, Presentation, Slide } from '@animotion/core';
    import '@animotion/core/theme';
    import { onMount } from 'svelte';
    import FloatingReactions from '@/components/tecturn/FloatingReactions.svelte';

    // The landing hero is a hand-written Animotion deck, not a Presenter
    // instance — the marketing copy lives here as slides, and the deck runs
    // embedded inside the page instead of owning the viewport.
    // Every slide is static and lands whole: punchy beats, no reveals. The
    // code morph is the one stepped element, because the morph is the demo.
    const reducedMotion =
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // The product loop, told as a refactor: rehearse, review with peers,
    // take the stage.
    const morphPages = [
        `function talk() {
  return draft
}`,
        `function talk() {
  const run = rehearse(draft)
  return review(run, peers)
}`,
        `function talk() {
  const run = rehearse(draft)
  const notes = review(run, peers)
  return takeTheStage(run, {
    reactions: true,
    translation: true,
  })
}`,
    ];

    // The "room talks back" slide uses the real presenter overlay: while that
    // slide is on, fake a room by spawning reactions through the same
    // component a live audience drives.
    let deckEl = $state<HTMLElement>();
    let floating = $state<FloatingReactions>();

    const roomEmojis = ['🔥', '👏', '🤯', '⚡', '❤️', '😄'];
    const roomMessages = ['ship it', '+1', 'nice'];
    let beat = 0;

    onMount(() => {
        if (reducedMotion) {
            return;
        }

        const interval = setInterval(() => {
            const active = deckEl?.querySelector(
                'section.present [data-reactions-cue]',
            );

            if (!active) {
                return;
            }

            beat += 1;

            if (beat % 7 === 0) {
                floating?.spawnMessage(
                    roomMessages[beat % roomMessages.length],
                );
            } else {
                floating?.spawnReaction(
                    roomEmojis[Math.floor(Math.random() * roomEmojis.length)],
                );
            }
        }, 450);

        return () => clearInterval(interval);
    });
</script>

<div
    class="relative h-full w-full"
    data-test="landing-deck"
    bind:this={deckEl}
>
    <FloatingReactions bind:this={floating} enabled={!reducedMotion} />
    <Presentation
        options={{
            hash: false,
            embedded: true,
            controls: true,
            progress: true,
            loop: true,
            keyboardCondition: 'focused',
            autoSlide: reducedMotion ? 0 : 4000,
            autoSlideStoppable: true,
        }}
    >
        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[3cqh] px-[8cqw] text-left"
            >
                <h1
                    class="font-display text-[9cqw] leading-none font-bold tracking-tight text-[#19191c]"
                >
                    Tecturn<span class="text-[#d97e0a]">.</span>
                </h1>
                <p class="text-[3.2cqw] text-[#65656d]">
                    The stage before the stage.
                </p>
                <p class="font-mono text-[1.7cqw] text-[#8d8d94]">
                    (this hero is a live deck, press →)
                </p>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-display text-[5.2cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    Talks don't succeed in a vacuum.
                </p>
                <p class="text-[2.4cqw] text-[#65656d]">
                    Nobody delivers a great one alone.
                </p>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.5cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    Rehearse like it's opening night.
                </h2>
                <div
                    class="flex items-center gap-[1.4cqw] rounded-lg border border-[#e4e4e7] bg-white px-[2cqw] py-[1.4cqh] font-mono text-[1.7cqw] text-[#65656d]"
                >
                    <span
                        class="h-[1.4cqw] w-[1.4cqw] rounded-full bg-[#d97e0a]"
                    ></span>
                    12:40 · slide 8 / 17
                    <span class="text-[#b06e10]">· rehearsing</span>
                </div>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    Every run is remembered.
                </h2>
                <p class="text-[2.4cqw] text-[#65656d]">
                    Timed per slide, snapshotted, ready to replay.
                </p>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.5cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    Your peers see it before the room does.
                </h2>
                <div
                    class="flex items-center gap-[1.2cqw] rounded-lg border border-[#e4e4e7] bg-white px-[2cqw] py-[1.4cqh] font-mono text-[1.7cqw] text-[#65656d]"
                >
                    <span class="text-[#b06e10]">@mika · slide 4</span>
                    “cut the YAML wall, just say it”
                </div>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    Build your circle of speakers.
                </h2>
                <p class="text-[2.4cqw] text-[#65656d]">
                    Trade reviews. Everyone goes on stage sharper.
                </p>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.5cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                >
                    The deck coaches you while you write.
                </h2>
                <div
                    class="flex items-center gap-[1.2cqw] rounded-lg border border-[#e4e4e7] bg-white px-[2cqw] py-[1.4cqh] font-mono text-[1.7cqw] text-[#65656d]"
                >
                    <span
                        class="h-[1.4cqw] w-[1.4cqw] rounded-full bg-[#d97e0a]"
                    ></span>
                    82 words · ~0:38
                    <span class="text-[#b06e10]">· trim this slide</span>
                </div>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4.6cqw] leading-tight font-semibold tracking-tight text-[#19191c]"
                    data-reactions-cue
                >
                    Then the room talks back.
                </h2>
                <p class="text-[2.4cqw] text-[#65656d]">
                    Reactions float across your slides. Captions translate you
                    live.
                </p>
            </div>
        </Slide>

        <Slide background="#fbfbfc" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[4cqh] px-[8cqw] text-left"
            >
                <h2
                    class="font-display text-[4cqw] font-semibold tracking-tight text-[#19191c]"
                >
                    The whole loop, built in.
                </h2>
                <div
                    class="w-[56cqw] [&_pre]:m-0 [&_pre]:overflow-auto [&_pre]:rounded-lg [&_pre]:bg-[#1a2029] [&_pre]:p-[1.6cqw] [&_pre]:text-[1.8cqw]! [&_pre]:leading-relaxed"
                >
                    <Code
                        codes={morphPages}
                        lang="js"
                        theme="github-dark"
                        autoIndent={false}
                    />
                </div>
            </div>
        </Slide>

        <Slide background="#131315" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[3cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-display text-[6.5cqw] leading-none font-bold tracking-tight text-[#f2f2f0]"
                >
                    Take the stage<span class="text-[#f5a623]">.</span>
                </p>
                <p class="text-[2.2cqw] text-[#8d8d94]">
                    Sign in and start your first deck.
                </p>
            </div>
        </Slide>
    </Presentation>
</div>

<style>
    /* Reveal chrome reads oversized on an embedded hero stage: shrink the
       nav arrows (em-driven) and quiet the autoplay pause button. */
    [data-test='landing-deck'] :global(.reveal .controls) {
        font-size: 7px;
    }

    [data-test='landing-deck'] :global(.reveal .playback) {
        transform: scale(0.7);
        transform-origin: bottom left;
        opacity: 0.5;
    }

    @media (max-width: 640px) {
        [data-test='landing-deck'] :global(.reveal .playback) {
            display: none;
        }
    }
</style>
