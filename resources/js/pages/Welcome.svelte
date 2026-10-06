<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Gauge from 'lucide-svelte/icons/gauge';
    import History from 'lucide-svelte/icons/history';
    import Radio from 'lucide-svelte/icons/radio';
    import Users from 'lucide-svelte/icons/users';
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
            icon: History,
            title: 'Rehearsals that remember',
            body: 'A rehearsal timer with per-slide timings, for Tecturn decks, PDFs, or Google Slides. Every run saves a snapshot, ready to replay or compare to the live talk.',
        },
        {
            icon: Users,
            title: 'A circle of reviewers',
            body: 'Reviews come from speakers who follow you. Send them a rehearsal and get slide-by-slide comments back, before the talk counts.',
        },
        {
            icon: Radio,
            title: 'A room that talks back',
            body: 'The audience scans a QR code and their reactions float across your slides live. Captions can translate you as you speak.',
        },
        {
            icon: Gauge,
            title: 'A coach while you write',
            body: 'Set a target length and every slide gets a speaking-time estimate while you build. The dense ones get flagged before you are on stage.',
        },
    ];
</script>

<AppHead title="The stage before the stage" />

{#snippet ctaButton()}
    {#if auth.user}
        <Link
            href={toUrl(dashboardUrl)}
            class="mt-5 inline-block rounded-md bg-[hsl(38_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(38_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(38_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(240_5%_7%)] focus-visible:outline-none"
        >
            Back to your decks
        </Link>
    {:else if features.canSelfRegister}
        <Link
            href={toUrl(login())}
            class="mt-5 inline-block rounded-md bg-[hsl(38_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(38_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(38_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(240_5%_7%)] focus-visible:outline-none"
        >
            Take the stage
        </Link>
    {:else if features.canRequestBeta}
        <Link
            href={toUrl(betaCreate())}
            class="mt-5 inline-block rounded-md bg-[hsl(38_91%_55%)] px-6 py-2.5 font-medium text-[hsl(36_45%_10%)] transition-colors hover:bg-[hsl(38_91%_62%)] focus-visible:ring-2 focus-visible:ring-[hsl(38_91%_55%)] focus-visible:ring-offset-2 focus-visible:ring-offset-[hsl(240_5%_7%)] focus-visible:outline-none"
        >
            Request beta access
        </Link>
    {/if}
{/snippet}

<div
    class="house flex min-h-screen flex-col text-[hsl(40_10%_95%)] antialiased"
>
    <header
        class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
    >
        <span
            class="font-display text-xl font-semibold tracking-tight select-none"
        >
            Tecturn<span class="text-[hsl(38_91%_55%)]">.</span>
        </span>
        <nav class="flex items-center gap-4">
            <Link
                href={toUrl(docs())}
                class="text-sm text-[hsl(240_4%_56%)] transition-colors hover:text-[hsl(240_5%_86%)]"
                data-test="landing-docs-link"
            >
                Docs
            </Link>
            {#if auth.user}
                <Link
                    href={toUrl(dashboardUrl)}
                    class="rounded-md border border-[hsl(240_4%_22%)] px-4 py-1.5 text-sm text-[hsl(240_5%_86%)] transition-colors hover:border-[hsl(37_40%_35%)] focus-visible:ring-2 focus-visible:ring-[hsl(38_91%_55%)] focus-visible:outline-none"
                >
                    Your decks
                </Link>
            {:else}
                <Link
                    href={toUrl(login())}
                    class="rounded-md border border-[hsl(240_4%_22%)] px-4 py-1.5 text-sm text-[hsl(240_5%_86%)] transition-colors hover:border-[hsl(37_40%_35%)] focus-visible:ring-2 focus-visible:ring-[hsl(38_91%_55%)] focus-visible:outline-none"
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
                class="stage-canvas aspect-video w-full overflow-hidden rounded-xl bg-[#fbfbfc] [container-type:size]"
            >
                <LandingDeck />
            </div>
            <p
                class="mt-3 hidden text-center font-mono text-xs text-[hsl(240_4%_56%)] sm:block"
            >
                click the stage, then
                <kbd
                    class="rounded border border-[hsl(240_4%_22%)] px-1.5 py-0.5"
                    >←</kbd
                >
                <kbd
                    class="rounded border border-[hsl(240_4%_22%)] px-1.5 py-0.5"
                    >→</kbd
                >
                to drive it
            </p>
        </div>

        <section class="mt-6 mb-6 max-w-xl text-center">
            <p class="text-lg leading-relaxed text-[hsl(240_5%_75%)]">
                Talks don't succeed in a vacuum. Tecturn is the stage before
                the stage: rehearse timed runs, get slide-by-slide reviews
                from your circle of speakers, then go live to a room that
                talks back. The deck above is the product doing its own pitch.
            </p>
            {@render ctaButton()}
        </section>

        <!-- Mission: set like a playbill epigraph under the stage. -->
        <section class="mt-16 w-full max-w-3xl text-center">
            <p
                class="font-mono text-xs tracking-[0.25em] text-[hsl(38_91%_55%)] uppercase"
            >
                The mission
            </p>
            <p
                class="mt-4 font-display text-2xl leading-snug font-semibold text-[hsl(40_10%_95%)] sm:text-3xl"
            >
                Give every technical speaker a stage before the stage: a place
                to rehearse until the timing is right, a circle of peers who
                see the talk before the room does, and an audience that talks
                back.
            </p>
            <p class="mt-4">
                <Link
                    href={toUrl(docs())}
                    class="text-sm text-[hsl(240_4%_56%)] underline-offset-4 transition-colors hover:text-[hsl(240_5%_86%)] hover:underline"
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
                        class="rounded-xl border border-[hsl(240_4%_22%)] bg-[hsl(240_5%_9%)] p-6 transition-colors hover:border-[hsl(37_40%_35%)]"
                    >
                        <feature.icon
                            class="h-5 w-5 text-[hsl(38_91%_55%)]"
                            aria-hidden="true"
                        />
                        <h2
                            class="mt-3 font-display font-semibold text-[hsl(40_10%_95%)]"
                        >
                            {feature.title}
                        </h2>
                        <p
                            class="mt-1.5 text-sm leading-relaxed text-[hsl(240_5%_70%)]"
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
                class="font-display text-3xl font-bold tracking-tight text-[hsl(40_10%_95%)]"
            >
                Ready for your next talk<span class="text-[hsl(38_91%_55%)]"
                    >?</span
                >
            </p>
            {@render ctaButton()}
        </section>
    </main>

    <footer
        class="px-6 py-3 text-center font-mono text-xs text-[hsl(240_4%_42%)]"
    >
        Tecturn
    </footer>
</div>

<style>
    .house {
        /* Neutral dark house with tungsten spill from above the stage. */
        background-color: hsl(240 5% 7%);
        background-image: radial-gradient(
            120% 60% at 50% -10%,
            hsl(38 60% 30% / 0.18),
            transparent 65%
        );
    }
</style>
