<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Blocks from 'lucide-svelte/icons/blocks';
    import Gauge from 'lucide-svelte/icons/gauge';
    import History from 'lucide-svelte/icons/history';
    import Radio from 'lucide-svelte/icons/radio';
    import AppHead from '@/components/AppHead.svelte';
    import LandingDeck from '@/components/LandingDeck.svelte';
    import { useFeatures } from '@/lib/features.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, docs, login } from '@/routes';
    import { create as betaCreate } from '@/routes/beta';
    import type { Team } from '@/types';

    const auth = $derived(page.props.auth);
    const currentTeam = $derived(page.props.currentTeam as Team | null);
    const dashboardUrl = $derived(
        currentTeam ? dashboard(currentTeam.slug) : '/',
    );

    const features = useFeatures();

    const showcase = [
        {
            icon: Blocks,
            title: 'Blocks, not bullet points',
            body: 'Text, code, boxes and QR codes, dragged anywhere on a 16:9 stage. Code blocks morph between steps like a refactor.',
        },
        {
            icon: Gauge,
            title: 'A pacing coach built in',
            body: 'Set your talk length and every slide gets a speaking-time estimate. The dense ones get flagged before you are on stage.',
        },
        {
            icon: Radio,
            title: 'A room that talks back',
            body: 'The audience scans a QR code and their reactions float across your slides live. Captions can translate you as you speak.',
        },
        {
            icon: History,
            title: 'Rehearsals that remember',
            body: 'A rehearsal timer with per-slide timings. Every run saves a snapshot of the deck, ready to replay or restore as a new deck.',
        },
    ];
</script>

<AppHead title="The stage for developer talks" />

{#snippet ctaButton()}
    {#if auth.user}
        <Link
            href={toUrl(dashboardUrl)}
            class="mt-5 inline-block rounded-md bg-[hsl(37_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(37_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(36_11%_7%)] focus-visible:outline-none"
        >
            Back to your decks
        </Link>
    {:else if features.canSelfRegister}
        <Link
            href={toUrl(login())}
            class="mt-5 inline-block rounded-md bg-[hsl(37_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(37_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(36_11%_7%)] focus-visible:outline-none"
        >
            Take the stage
        </Link>
    {:else if features.canRequestBeta}
        <Link
            href={toUrl(betaCreate())}
            class="mt-5 inline-block rounded-md bg-[hsl(37_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(37_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(36_11%_7%)] focus-visible:outline-none"
        >
            Request beta access
        </Link>
    {/if}
{/snippet}

<div
    class="house flex min-h-screen flex-col text-[hsl(40_30%_96%)] antialiased"
>
    <header
        class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
    >
        <span
            class="font-display text-xl font-semibold tracking-tight select-none"
        >
            Tecturn<span class="text-[hsl(37_91%_55%)]">.</span>
        </span>
        <nav class="flex items-center gap-4">
            <Link
                href={toUrl(docs())}
                class="text-sm text-[hsl(37_6%_55%)] transition-colors hover:text-[hsl(40_20%_86%)]"
                data-test="landing-docs-link"
            >
                Docs
            </Link>
            {#if auth.user}
                <Link
                    href={toUrl(dashboardUrl)}
                    class="rounded-md border border-[hsl(34_9%_22%)] px-4 py-1.5 text-sm text-[hsl(40_20%_86%)] transition-colors hover:border-[hsl(37_40%_35%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:outline-none"
                >
                    Your decks
                </Link>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="rounded-md border border-[hsl(34_9%_22%)] px-4 py-1.5 text-sm text-[hsl(40_20%_86%)] transition-colors hover:border-[hsl(37_40%_35%)] focus-visible:ring-2 focus-visible:ring-[hsl(37_91%_55%)] focus-visible:outline-none"
                >
                    Log in
                </Link>
            {/if}
        </nav>
    </header>

    <main class="flex grow flex-col items-center px-6">
        <!-- The hero: a real Animotion deck on a lit 16:9 stage. -->
        <div class="mt-2 w-full max-w-5xl">
            <div
                class="stage-canvas aspect-video w-full overflow-hidden rounded-xl bg-[#faf7f0] [container-type:size]"
            >
                <LandingDeck />
            </div>
            <p
                class="mt-3 hidden text-center font-mono text-xs text-[hsl(37_6%_55%)] sm:block"
            >
                click the stage, then
                <kbd
                    class="rounded border border-[hsl(34_9%_22%)] px-1.5 py-0.5"
                    >←</kbd
                >
                <kbd
                    class="rounded border border-[hsl(34_9%_22%)] px-1.5 py-0.5"
                    >→</kbd
                >
                to drive it
            </p>
        </div>

        <section class="mt-6 mb-6 max-w-xl text-center">
            <p class="text-lg leading-relaxed text-[hsl(40_15%_75%)]">
                Build slides out of blocks, wire their reveal order in a flow
                graph, and present with live translation and floating audience
                reactions. A built-in pacing coach flags dense slides and keeps
                your talk on time. The deck above is the product doing its own
                pitch.
            </p>
            {@render ctaButton()}
        </section>

        <!-- Mission: set like a playbill epigraph under the stage. -->
        <section class="mt-16 w-full max-w-3xl text-center">
            <p
                class="font-mono text-xs tracking-[0.25em] text-[hsl(37_91%_55%)] uppercase"
            >
                The mission
            </p>
            <p
                class="mt-4 font-display text-2xl leading-snug font-semibold text-[hsl(40_30%_96%)] sm:text-3xl"
            >
                Give technical speakers the tools to build slides as clear and
                efficient as good code, the means to rehearse until the timing
                is right, and a stage where the audience talks back.
            </p>
            <p class="mt-4">
                <Link
                    href={toUrl(docs())}
                    class="text-sm text-[hsl(37_6%_55%)] underline-offset-4 transition-colors hover:text-[hsl(40_20%_86%)] hover:underline"
                >
                    See how it works in the docs
                </Link>
            </p>
        </section>

        <!-- Feature showcase -->
        <section class="mt-16 w-full max-w-5xl">
            <div class="grid gap-4 sm:grid-cols-2">
                {#each showcase as feature (feature.title)}
                    <div
                        class="rounded-xl border border-[hsl(34_9%_22%)] bg-[hsl(36_11%_9%)] p-6 transition-colors hover:border-[hsl(37_40%_35%)]"
                    >
                        <feature.icon
                            class="h-5 w-5 text-[hsl(37_91%_55%)]"
                            aria-hidden="true"
                        />
                        <h2
                            class="mt-3 font-display font-semibold text-[hsl(40_30%_96%)]"
                        >
                            {feature.title}
                        </h2>
                        <p
                            class="mt-1.5 text-sm leading-relaxed text-[hsl(40_15%_70%)]"
                        >
                            {feature.body}
                        </p>
                    </div>
                {/each}
            </div>
        </section>

        <!-- Closing CTA -->
        <section class="mt-16 mb-10 w-full max-w-3xl text-center">
            <p
                class="font-display text-3xl font-bold tracking-tight text-[hsl(40_30%_96%)]"
            >
                Ready for your next talk<span class="text-[hsl(37_91%_55%)]"
                    >?</span
                >
            </p>
            {@render ctaButton()}
        </section>
    </main>

    <footer
        class="px-6 py-3 text-center font-mono text-xs text-[hsl(37_6%_42%)]"
    >
        Tecturn
    </footer>
</div>

<style>
    .house {
        /* Dark theater house with tungsten spill from above the stage. */
        background-color: hsl(36 11% 7%);
        background-image: radial-gradient(
            120% 60% at 50% -10%,
            hsl(37 60% 30% / 0.2),
            transparent 65%
        );
    }
</style>
