<script module lang="ts">
    import { dashboard } from '@/routes';
    import type { Team } from '@/types';

    export const layout = (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
            { title: 'Session', href: '#' },
        ],
    });
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Radio from 'lucide-svelte/icons/radio';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import { Button } from '@/components/ui/button';
    import { edit } from '@/routes/presentations';

    type SessionDetail = {
        id: number;
        presentation_id: number;
        presentation_name: string;
        started_at: string;
        ended_at: string | null;
        is_live: boolean;
        duration_seconds: number;
        viewer_count: number;
        reaction_total: number;
        reaction_counts: Record<string, number>;
        word_count: number | null;
        slide_timings: { slide: number; seconds: number }[];
        reaction_slides: {
            slide: number;
            reactions: Record<string, number>;
        }[];
    };

    let {
        session,
        rehearsedSeconds,
        rehearsalCount = 0,
    }: {
        session: SessionDetail;
        // Average rehearsed seconds keyed by slide index. PHP serializes a
        // contiguous 0-based map as a JSON array, a sparse one as an object —
        // index access below handles both.
        rehearsedSeconds: Record<number, number> | number[];
        rehearsalCount?: number;
    } = $props();

    const teamSlug = $derived(page.props.currentTeam?.slug ?? '');

    const reactionsFor = (slide: number): [string, number][] => {
        const entry = session.reaction_slides.find(
            (candidate) => candidate.slide === slide,
        );

        return entry
            ? Object.entries(entry.reactions).sort(([, a], [, b]) => b - a)
            : [];
    };

    const rehearsedFor = (slide: number): number | null => {
        const value = (rehearsedSeconds as Record<number, number>)[slide];

        return typeof value === 'number' ? value : null;
    };

    const formatTime = (seconds: number): string => {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;

        return `${m}:${String(s).padStart(2, '0')}`;
    };

    const formatDuration = (seconds: number): string => {
        if (seconds < 60) {
            return `${seconds}s`;
        }

        const m = Math.floor(seconds / 60);
        const h = Math.floor(m / 60);

        return h > 0 ? `${h}h ${m % 60}m` : `${m}m ${seconds % 60}s`;
    };

    const startedLabel = new Date(session.started_at).toLocaleString(
        undefined,
        { dateStyle: 'medium', timeStyle: 'short' },
    );

    const topEmoji = $derived(
        Object.entries(session.reaction_counts).sort(
            ([, a], [, b]) => b - a,
        )[0]?.[0] ?? null,
    );

    const stats = $derived([
        { label: 'Duration', value: formatDuration(session.duration_seconds) },
        { label: 'Viewers', value: `${session.viewer_count}` },
        { label: 'Reactions', value: `${session.reaction_total}` },
        { label: 'Loudest reaction', value: topEmoji ?? '—' },
    ]);

    // Bars scale against the longest live or rehearsed slide so the two are
    // visually comparable.
    const maxSeconds = $derived(
        Math.max(
            1,
            ...session.slide_timings.map((timing) => timing.seconds),
            ...session.slide_timings.map(
                (timing) => rehearsedFor(timing.slide) ?? 0,
            ),
        ),
    );

    const sortedReactions = $derived(
        Object.entries(session.reaction_counts).sort(([, a], [, b]) => b - a),
    );
</script>

<AppHead title="Session · {session.presentation_name}" />

<div class="mx-auto flex w-full max-w-4xl flex-col gap-8 p-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <Heading
                variant="small"
                title={session.presentation_name}
                description="Live session · {startedLabel}"
            />
            {#if session.is_live}
                <span
                    class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-500"
                >
                    <Radio class="h-3 w-3" /> Still live
                </span>
            {/if}
        </div>
        <Button
            variant="outline"
            onclick={() =>
                router.visit(
                    edit({
                        current_team: teamSlug,
                        presentation: session.presentation_id,
                    }).url,
                )}
        >
            Open deck
        </Button>
    </div>

    <!-- Session stats, same strip as the dashboard. -->
    <section
        class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-4"
    >
        {#each stats as stat (stat.label)}
            <div class="bg-card p-5">
                <p
                    class="font-mono text-3xl font-bold tabular-nums text-foreground"
                >
                    {stat.value}
                </p>
                <p
                    class="mt-1 text-xs font-semibold uppercase tracking-wider text-muted-foreground"
                >
                    {stat.label}
                </p>
            </div>
        {/each}
    </section>

    {#if session.reaction_total > 0}
        <div class="flex flex-wrap gap-1.5">
            {#each sortedReactions as [emoji, count] (emoji)}
                <span
                    class="flex items-center gap-1 rounded-md bg-accent px-2 py-0.5 text-sm"
                >
                    <span aria-hidden="true">{emoji}</span>
                    <span
                        class="font-mono text-xs tabular-nums text-muted-foreground"
                        >{count}</span
                    >
                </span>
            {/each}
        </div>
    {/if}

    <!-- Live vs rehearsed, slide by slide. -->
    <section class="flex flex-col gap-3">
        <div class="flex items-baseline justify-between">
            <h2 class="text-sm font-semibold text-foreground">
                Time per slide
            </h2>
            {#if rehearsalCount > 0}
                <p class="text-xs text-muted-foreground">
                    vs the average of {rehearsalCount} rehearsal{rehearsalCount ===
                    1
                        ? ''
                        : 's'}
                </p>
            {/if}
        </div>

        {#if session.slide_timings.length > 0}
            <ul class="flex flex-col gap-2">
                {#each session.slide_timings as timing (timing.slide)}
                    {@const rehearsed = rehearsedFor(timing.slide)}
                    {@const delta =
                        rehearsed !== null ? timing.seconds - rehearsed : null}
                    {@const slideReactions = reactionsFor(timing.slide)}
                    <li
                        class="rounded-xl border border-border bg-card p-3"
                        data-test="session-slide-timing"
                    >
                        <div class="flex items-baseline justify-between text-sm">
                            <span class="font-medium text-foreground">
                                Slide {timing.slide + 1}
                            </span>
                            <span
                                class="font-mono tabular-nums text-muted-foreground"
                            >
                                {formatTime(timing.seconds)}
                                {#if rehearsed !== null}
                                    <span class="text-xs">
                                        / {formatTime(rehearsed)} rehearsed
                                    </span>
                                {/if}
                                {#if delta !== null && Math.abs(delta) >= 5}
                                    <span
                                        class="ml-1 text-xs font-semibold {delta >
                                        0
                                            ? 'text-red-500'
                                            : 'text-emerald-600'}"
                                        data-test="session-slide-delta"
                                    >
                                        {delta > 0 ? '+' : '−'}{formatTime(
                                            Math.abs(delta),
                                        )}
                                    </span>
                                {/if}
                            </span>
                        </div>
                        <!-- Live bar (amber) over the rehearsed baseline
                             (muted), same scale. -->
                        <div
                            class="mt-2 h-1.5 overflow-hidden rounded-full bg-accent"
                        >
                            <div
                                class="h-full rounded-full bg-amber-500"
                                style="width: {Math.round(
                                    (timing.seconds / maxSeconds) * 100,
                                )}%"
                            ></div>
                        </div>
                        {#if rehearsed !== null}
                            <div
                                class="mt-1 h-1.5 overflow-hidden rounded-full bg-accent"
                            >
                                <div
                                    class="h-full rounded-full bg-zinc-400"
                                    style="width: {Math.round(
                                        (rehearsed / maxSeconds) * 100,
                                    )}%"
                                ></div>
                            </div>
                        {/if}
                        {#if slideReactions.length > 0}
                            <div
                                class="mt-2 flex flex-wrap gap-1.5"
                                data-test="session-slide-reactions"
                            >
                                {#each slideReactions as [emoji, count] (emoji)}
                                    <span
                                        class="flex items-center gap-1 rounded-md bg-accent px-1.5 py-0.5 text-xs"
                                    >
                                        <span aria-hidden="true">{emoji}</span>
                                        <span
                                            class="font-mono tabular-nums text-muted-foreground"
                                            >{count}</span
                                        >
                                    </span>
                                {/each}
                            </div>
                        {/if}
                    </li>
                {/each}
            </ul>
            {#if rehearsalCount === 0}
                <p class="text-xs text-muted-foreground">
                    Rehearse this deck to get a comparison baseline here.
                </p>
            {/if}
        {:else}
            <p
                class="rounded-xl border border-dashed border-border bg-card px-4 py-8 text-center text-sm text-muted-foreground"
            >
                {session.is_live
                    ? 'Per-slide timings land when the session closes.'
                    : 'No per-slide timings were recorded for this session.'}
            </p>
        {/if}
    </section>
</div>
