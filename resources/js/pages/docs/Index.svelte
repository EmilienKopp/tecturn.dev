<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import DocsQuickStartDeck from '@/components/docs/DocsQuickStartDeck.svelte';
    import PracticeDock from '@/components/tecturn/PracticeDock.svelte';
    import PresenterDock from '@/components/tecturn/PresenterDock.svelte';
    import { toUrl } from '@/lib/utils';
    import { dashboard, home, login } from '@/routes';
    import type { Team } from '@/types';
    import type { TalkSettings } from '@/types/generated';

    const auth = $derived(page.props.auth);
    const currentTeam = $derived(page.props.currentTeam as Team | null);

    // Prop stand-ins for the live UI embedded below. The components are the
    // real ones from the app, rendered inert instead of screenshotted.
    const demoTalkSettings: TalkSettings = {
        showReactions: true,
        showDock: true,
        showTranslation: false,
        timerMode: 'elapsed',
        durationMinutes: 20,
        autoSave: true,
        footer: {
            enabled: false,
            xHandle: null,
            githubHandle: null,
            hashtag: null,
            bgColor: '#191510',
            fontColor: '#f6efe1',
            showInDock: false,
        },
    };

    const sections = [
        { id: 'decks', title: 'Build a deck' },
        { id: 'present', title: 'Present live' },
        { id: 'rehearse', title: 'Rehearse' },
        { id: 'share', title: 'Share & export' },
    ];
</script>

<AppHead title="Documentation" />

<div class="min-h-screen bg-background text-foreground antialiased">
    <header
        class="sticky top-0 z-10 border-b border-border bg-background/90 backdrop-blur"
    >
        <div
            class="mx-auto flex w-full max-w-4xl items-center justify-between px-6 py-3"
        >
            <Link
                href={toUrl(home())}
                class="font-display text-lg font-semibold tracking-tight"
            >
                Tecturn<span class="text-amber-500">.</span>
                <span class="ml-2 text-sm font-normal text-muted-foreground">
                    Docs
                </span>
            </Link>
            {#if auth.user && currentTeam}
                <Link
                    href={toUrl(dashboard(currentTeam.slug))}
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Back to dashboard
                </Link>
            {:else if !auth.user}
                <Link
                    href={toUrl(login())}
                    class="text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    Log in
                </Link>
            {/if}
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-4xl flex-col gap-14 px-6 py-10">
        <div class="flex flex-col gap-4">
            <h1 class="font-display text-3xl font-bold tracking-tight">
                How Tecturn works
            </h1>
            <p class="max-w-2xl text-muted-foreground">
                From empty deck to live talk in four steps. The stage below is a
                real deck: click it, then drive it with
                <kbd class="rounded border border-border px-1.5 py-0.5 text-xs"
                    >←</kbd
                >
                <kbd class="rounded border border-border px-1.5 py-0.5 text-xs"
                    >→</kbd
                >.
            </p>
            <nav class="flex flex-wrap gap-2 text-sm">
                {#each sections as section (section.id)}
                    <a
                        href="#{section.id}"
                        class="rounded-full border border-border px-3 py-1 text-muted-foreground transition-colors hover:border-amber-500 hover:text-foreground"
                    >
                        {section.title}
                    </a>
                {/each}
            </nav>
        </div>

        <!-- Build -->
        <section id="decks" class="flex scroll-mt-20 flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Build a deck</h2>
            <div
                class="aspect-video w-full overflow-hidden rounded-xl border border-border bg-[#faf7f0] [container-type:size]"
            >
                <DocsQuickStartDeck />
            </div>
            <ol class="list-decimal space-y-2 pl-5 text-muted-foreground">
                <li>
                    Go to <strong class="text-foreground">Presentations</strong>
                    and hit
                    <strong class="text-foreground">New presentation</strong>.
                    Name it, click Create.
                </li>
                <li>
                    In the editor, click an empty spot on the slide. A picker
                    appears with the block types:
                    <strong class="text-foreground">Text</strong>,
                    <strong class="text-foreground">Code</strong>,
                    <strong class="text-foreground">Box</strong> and
                    <strong class="text-foreground">QR</strong>. Drag a block to
                    position it, click it to edit its content and style.
                </li>
                <li>
                    Open <strong class="text-foreground">Settings</strong> →
                    <strong class="text-foreground">Talk length…</strong> and enter
                    your slot in minutes. Each slide then shows a speaking-time estimate
                    and the linter flags slides that are too dense for their share
                    of the talk.
                </li>
                <li>
                    Your work auto-saves (the toggle is in the toolbar). Add
                    slides, reorder, repeat.
                </li>
            </ol>
        </section>

        <!-- Present -->
        <section id="present" class="flex scroll-mt-20 flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Present live</h2>
            <p class="text-muted-foreground">
                In the editor, open
                <strong class="text-foreground">Present</strong> →
                <strong class="text-foreground">Go Live</strong>. That opens the
                presenter view and starts recording the session. Point the
                audience at the QR code in the dock (or copy the reaction URL
                from the toolbar): it opens a page on their phones where their
                emoji reactions float across your slides live. Viewer counts and
                reactions land in the dashboard afterwards.
            </p>
            <p class="text-muted-foreground">
                The dock sits next to your slides. The switch at the bottom
                toggles reactions on screen, and the per-slide timer turns
                amber, then red, as you overrun a slide's share of the talk:
            </p>
            <div class="flex justify-center rounded-xl bg-zinc-950 p-6">
                <div
                    class="pointer-events-none h-[560px] select-none"
                    inert
                    aria-hidden="true"
                >
                    <PresenterDock
                        viewerUrl="https://tecturn.app/present/demo"
                        talkSettings={demoTalkSettings}
                        slideCount={12}
                        currentSlide={3}
                        viewerCount={24}
                        reactionTotal={132}
                        showReactions={true}
                    />
                </div>
            </div>
        </section>

        <!-- Rehearse -->
        <section id="rehearse" class="flex scroll-mt-20 flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Rehearse</h2>
            <p class="text-muted-foreground">
                Both live in the same
                <strong class="text-foreground">Present</strong> menu, and neither
                touches your stats:
            </p>
            <ul class="list-disc space-y-2 pl-5 text-muted-foreground">
                <li>
                    <strong class="text-foreground">Test run</strong>: the full
                    presenter view with no analytics session. Good for checking
                    slides and reactions end to end.
                </li>
                <li>
                    <strong class="text-foreground">Practice</strong>: adds a
                    start/pause/stop timer. Hit Stop and the run is saved to the
                    <strong class="text-foreground">Rehearsals</strong>
                    page in the sidebar, together with a frozen snapshot of the deck.
                    Open a run there to replay the slides as they were, see the time
                    per slide, download the snapshot as JSON, or create a new deck
                    from it.
                </li>
            </ul>
            <p class="text-muted-foreground">
                The clock and per-slide timings only run between Start and Stop;
                paused time never counts:
            </p>
            <div class="flex justify-center rounded-xl bg-zinc-950 p-6">
                <div
                    class="pointer-events-none select-none"
                    inert
                    aria-hidden="true"
                >
                    <PracticeDock
                        talkSettings={demoTalkSettings}
                        slideCount={12}
                        currentSlide={2}
                        onFinish={() => {}}
                    />
                </div>
            </div>
        </section>

        <!-- Share -->
        <section id="share" class="flex scroll-mt-20 flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Share & export</h2>
            <ul class="list-disc space-y-2 pl-5 text-muted-foreground">
                <li>
                    <strong class="text-foreground">Export</strong>: in the
                    editor's Export menu, download the deck as JSON, a
                    standalone Svelte project, or a single-file web component (a
                    script tag that renders the deck on any site, fonts
                    included).
                </li>
                <li>
                    <strong class="text-foreground">Import</strong>: on the
                    Presentations page, paste or upload deck JSON (a rehearsal
                    snapshot works too) to create a new presentation from it.
                </li>
            </ul>
        </section>

        <footer
            class="border-t border-border pt-6 pb-10 text-sm text-muted-foreground"
        >
            Something the docs don't answer? They should. Tell us.
        </footer>
    </main>
</div>
