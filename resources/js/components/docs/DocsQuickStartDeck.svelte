<script lang="ts">
    import { Presentation, Slide, Transition } from '@animotion/core';
    import '@animotion/core/theme';

    // Hand-written Animotion deck for the docs quick start. Instructional on
    // purpose: every slide says what to click, in the words the UI uses.
</script>

<div class="h-full w-full" data-test="docs-quickstart-deck">
    <Presentation
        options={{
            hash: false,
            embedded: true,
            controls: true,
            progress: true,
            keyboardCondition: 'focused',
        }}
    >
        <Slide background="#faf7f0" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.5cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-mono text-[1.8cqw] tracking-wide text-[#a5947a] uppercase"
                >
                    Step 1 · Create a deck
                </p>
                <h2
                    class="font-display text-[4.4cqw] font-semibold tracking-tight text-[#221c12]"
                >
                    Presentations →
                    <span class="text-[#b06e10]">New presentation</span>
                </h2>
                <p class="text-[2.2cqw] text-[#6e6250]">
                    Name it, hit Create. You land in the editor on an empty
                    slide.
                </p>
                <p class="font-mono text-[1.6cqw] text-[#a5947a]">
                    press → for the next step
                </p>
            </div>
        </Slide>

        <Slide background="#faf7f0" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.2cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-mono text-[1.8cqw] tracking-wide text-[#a5947a] uppercase"
                >
                    Step 2 · Add blocks
                </p>
                <h2
                    class="font-display text-[4.4cqw] font-semibold tracking-tight text-[#221c12]"
                >
                    Click an empty spot on the slide.
                </h2>
                <Transition order={1}>
                    <div class="flex gap-[1cqw]">
                        {#each ['Text', 'Code', 'Box', 'QR'] as label (label)}
                            <span
                                class="rounded-md border border-[#e4d9c4] bg-white px-[1.8cqw] py-[0.8cqh] text-[1.8cqw] text-[#221c12]"
                            >
                                {label}
                            </span>
                        {/each}
                    </div>
                </Transition>
                <Transition order={2}>
                    <p class="text-[2.2cqw] text-[#6e6250]">
                        A picker appears. Choose a type, then drag the block
                        where you want it and click it to edit.
                    </p>
                </Transition>
            </div>
        </Slide>

        <Slide background="#faf7f0" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.2cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-mono text-[1.8cqw] tracking-wide text-[#a5947a] uppercase"
                >
                    Step 3 · Set your talk length
                </p>
                <h2
                    class="font-display text-[4.4cqw] font-semibold tracking-tight text-[#221c12]"
                >
                    Settings → <span class="text-[#b06e10]">Talk length…</span>
                </h2>
                <p class="text-[2.2cqw] text-[#6e6250]">
                    Enter your slot in minutes. The editor now shows a
                    speaking-time estimate per slide and flags the ones to trim.
                </p>
            </div>
        </Slide>

        <Slide background="#191510" class="h-full w-full">
            <div
                class="flex h-full w-full flex-col items-start justify-center gap-[2.2cqh] px-[8cqw] text-left"
            >
                <p
                    class="font-mono text-[1.8cqw] tracking-wide text-[#7a6c56] uppercase"
                >
                    Step 4 · Take the stage
                </p>
                <h2
                    class="font-display text-[4.4cqw] font-semibold tracking-tight text-[#f6efe1]"
                >
                    Present → <span class="text-[#f0a223]">Go Live</span>
                </h2>
                <p class="text-[2.2cqw] text-[#a5947a]">
                    Same menu: Test run to check everything without recording
                    stats, Rehearse against the clock.
                </p>
            </div>
        </Slide>
    </Presentation>
</div>

<style>
    [data-test='docs-quickstart-deck'] :global(.reveal .controls) {
        font-size: 6px;
    }
</style>
