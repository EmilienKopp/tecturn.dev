<script module lang="ts">
    import { index } from '@/routes/calendar';
    import type { Team } from '@/types';

    export const layout = (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: props.currentTeam ? index(props.currentTeam.slug) : '/',
            },
        ],
    });
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import { Button, Input, Modal } from 'daisy-svelte';
    import CalendarDays from 'lucide-svelte/icons/calendar-days';
    import ChevronLeft from 'lucide-svelte/icons/chevron-left';
    import ChevronRight from 'lucide-svelte/icons/chevron-right';
    import Plus from 'lucide-svelte/icons/plus';
    import AppHead from '@/components/AppHead.svelte';
    import Heading from '@/components/Heading.svelte';
    import PresentMenu from '@/components/PresentMenu.svelte';
    import { edit as editPresentation } from '@/routes/presentations';
    import {
        destroy as destroyEvent,
        store as storeEvent,
        update as updateEvent,
    } from '@/routes/talk-events';

    type EventRow = {
        id: number;
        name: string;
        date: string; // YYYY-MM-DD
        start_time: string | null; // HH:MM
        talk_id: number | null;
        talk_title: string | null;
    };

    type TalkRow = {
        id: number;
        title: string;
        deck_count: number;
        latest_major: number | null;
        latest_presentation_id: number | null;
        generated_at: string | null;
    };

    let {
        events = [],
        talks = [],
    }: {
        events?: EventRow[];
        talks?: TalkRow[];
    } = $props();

    const teamSlug = $derived(page.props.currentTeam?.slug ?? '');

    // --- Month grid ---
    const today = new Date();

    const dateKey = (date: Date): string =>
        `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

    let cursorYear = $state(today.getFullYear());
    let cursorMonth = $state(today.getMonth());

    const monthLabel = $derived(
        new Date(cursorYear, cursorMonth, 1).toLocaleDateString(undefined, {
            month: 'long',
            year: 'numeric',
        }),
    );

    const weekdays = $derived.by(() => {
        // Monday-first headers, localized via an arbitrary known Monday.
        const monday = new Date(2026, 0, 5);

        return Array.from({ length: 7 }, (_, i) =>
            new Date(
                monday.getFullYear(),
                monday.getMonth(),
                monday.getDate() + i,
            ).toLocaleDateString(undefined, { weekday: 'short' }),
        );
    });

    type DayCell = {
        key: string;
        day: number;
        inMonth: boolean;
        today: boolean;
    };

    const dayCells = $derived.by((): DayCell[] => {
        const first = new Date(cursorYear, cursorMonth, 1);
        // Monday-first offset: getDay() is 0 for Sunday.
        const offset = (first.getDay() + 6) % 7;
        const start = new Date(cursorYear, cursorMonth, 1 - offset);
        const todayKey = dateKey(today);
        const cells: DayCell[] = [];

        for (let i = 0; i < 42; i++) {
            const date = new Date(
                start.getFullYear(),
                start.getMonth(),
                start.getDate() + i,
            );

            cells.push({
                key: dateKey(date),
                day: date.getDate(),
                inMonth: date.getMonth() === cursorMonth,
                today: dateKey(date) === todayKey,
            });
        }

        // Drop a trailing all-out-of-month week so short months stay 5 rows.
        return cells.slice(35).some((cell) => cell.inMonth)
            ? cells
            : cells.slice(0, 35);
    });

    const eventsByDate = $derived.by(() => {
        const map: Record<string, EventRow[]> = {};

        for (const event of events) {
            (map[event.date] ??= []).push(event);
        }

        return map;
    });

    const upcoming = $derived(
        events.filter((event) => event.date >= dateKey(today)).slice(0, 6),
    );

    const goToPreviousMonth = () => {
        const date = new Date(cursorYear, cursorMonth - 1, 1);

        cursorYear = date.getFullYear();
        cursorMonth = date.getMonth();
    };

    const goToNextMonth = () => {
        const date = new Date(cursorYear, cursorMonth + 1, 1);

        cursorYear = date.getFullYear();
        cursorMonth = date.getMonth();
    };

    const goToToday = () => {
        cursorYear = today.getFullYear();
        cursorMonth = today.getMonth();
    };

    const formatEventDate = (value: string): string =>
        new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        });

    // --- Schedule / edit dialog ---
    let dialogOpen = $state(false);
    let editingId = $state<number | null>(null);
    let formName = $state('');
    let formDate = $state('');
    let formTime = $state('');
    let formTalkId = $state('');
    let submitting = $state(false);

    const openCreate = (date?: string): void => {
        editingId = null;
        formName = '';
        formDate = date ?? dateKey(today);
        formTime = '';
        formTalkId = '';
        dialogOpen = true;
    };

    const openEdit = (event: EventRow): void => {
        editingId = event.id;
        formName = event.name;
        formDate = event.date;
        formTime = event.start_time ?? '';
        formTalkId = event.talk_id !== null ? String(event.talk_id) : '';
        dialogOpen = true;
    };

    const formErrors = $derived(
        (page.props.errors ?? {}) as Record<string, string>,
    );

    // Drafts still generating are not linkable, but a talk already attached to
    // the event being edited stays listed so saving doesn't drop the link.
    const linkableTalks = $derived(
        talks.filter(
            (talk) =>
                talk.generated_at !== null || String(talk.id) === formTalkId,
        ),
    );

    // The picked talk resolves to its latest deck for the Present menu.
    const formPresentationId = $derived(
        formTalkId !== ''
            ? (talks.find((talk) => String(talk.id) === formTalkId)
                  ?.latest_presentation_id ?? null)
            : null,
    );

    const submitEvent = (submission: SubmitEvent): void => {
        submission.preventDefault();

        const payload = {
            name: formName,
            date: formDate,
            start_time: formTime !== '' ? formTime : null,
            talk_id: formTalkId !== '' ? Number(formTalkId) : null,
        };
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen = false;
            },
            onFinish: () => {
                submitting = false;
            },
        };

        submitting = true;

        if (editingId === null) {
            router.post(storeEvent(teamSlug).url, payload, options);
        } else {
            router.put(
                updateEvent({ current_team: teamSlug, talk_event: editingId })
                    .url,
                payload,
                options,
            );
        }
    };

    const deleteEvent = (): void => {
        if (editingId === null) {
            return;
        }

        submitting = true;
        router.delete(
            destroyEvent({ current_team: teamSlug, talk_event: editingId }).url,
            {
                preserveScroll: true,
                onSuccess: () => {
                    dialogOpen = false;
                },
                onFinish: () => {
                    submitting = false;
                },
            },
        );
    };
</script>

<AppHead title="Calendar" />

<div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <Heading
            variant="small"
            title="Calendar"
            description="Click on days to schedule talks, or edit/present existing ones."
        />
        <Button onclick={() => openCreate()} data-test="schedule-talk-button">
            <Plus class="h-4 w-4" /> Schedule a talk
        </Button>
    </div>

    <section class="rounded-xl border border-border bg-card">
        <div
            class="flex items-center justify-between gap-3 border-b border-border px-4 py-3"
        >
            <h2 class="font-display text-sm font-semibold text-foreground">
                {monthLabel}
            </h2>
            <div class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="sm"
                    onclick={goToPreviousMonth}
                    aria-label="Previous month"
                    data-test="calendar-prev-month"
                >
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <Button variant="base" outline size="sm" onclick={goToToday}>
                    Today
                </Button>
                <Button
                    variant="ghost"
                    size="sm"
                    onclick={goToNextMonth}
                    aria-label="Next month"
                    data-test="calendar-next-month"
                >
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div
            class="grid grid-cols-7 border-b border-border text-center text-xs font-medium text-muted-foreground"
        >
            {#each weekdays as weekday (weekday)}
                <div class="py-2">{weekday}</div>
            {/each}
        </div>

        <div class="grid grid-cols-7">
            {#each dayCells as cell (cell.key)}
                <!-- svelte-ignore a11y_no_static_element_interactions, a11y_click_events_have_key_events -->
                <div
                    class="min-h-24 border-r border-b border-border/60 p-1.5 last:border-r-0 [&:nth-child(7n)]:border-r-0 {cell.inMonth
                        ? 'cursor-pointer hover:bg-accent/40'
                        : 'bg-muted/30'}"
                    onclick={() => cell.inMonth && openCreate(cell.key)}
                    data-test="calendar-day"
                >
                    <span
                        class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs tabular-nums {cell.today
                            ? 'bg-primary font-semibold text-primary-foreground'
                            : cell.inMonth
                              ? 'text-foreground'
                              : 'text-muted-foreground/50'}"
                    >
                        {cell.day}
                    </span>
                    <div class="mt-1 flex flex-col gap-1">
                        {#each eventsByDate[cell.key] ?? [] as event (event.id)}
                            <button
                                type="button"
                                class="w-full truncate rounded border border-primary/30 bg-primary/10 px-1.5 py-0.5 text-left text-xs text-foreground hover:bg-primary/20"
                                onclick={(click: MouseEvent) => {
                                    click.stopPropagation();
                                    openEdit(event);
                                }}
                                data-test="calendar-event-chip"
                            >
                                {#if event.start_time}
                                    <span
                                        class="font-mono text-[10px] text-muted-foreground"
                                    >
                                        {event.start_time}
                                    </span>
                                {/if}
                                {event.name}
                            </button>
                        {/each}
                    </div>
                </div>
            {/each}
        </div>
    </section>

    {#if upcoming.length > 0}
        <section class="flex flex-col gap-3">
            <h2 class="text-sm font-semibold text-foreground">Coming up</h2>
            <ul class="flex flex-col gap-2">
                {#each upcoming as event (event.id)}
                    <li
                        class="rounded-xl border border-border bg-card p-4"
                        data-test="upcoming-event-row"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate font-display font-semibold text-foreground"
                                >
                                    {event.name}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {formatEventDate(event.date)}
                                    {event.start_time
                                        ? `· ${event.start_time}`
                                        : ''}
                                    {event.talk_title
                                        ? `· ${event.talk_title}`
                                        : ''}
                                </p>
                            </div>
                            <Button
                                variant="base"
                                outline
                                size="sm"
                                onclick={() => openEdit(event)}
                            >
                                Edit
                            </Button>
                        </div>
                    </li>
                {/each}
            </ul>
        </section>
    {:else if events.length === 0}
        <div
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-border bg-card px-6 py-10 text-center"
        >
            <CalendarDays class="h-6 w-6 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                Nothing scheduled yet. Pick a day above or use "Schedule a talk"
                to put your next talk on the calendar.
            </p>
        </div>
    {/if}
</div>

<Modal bind:open={dialogOpen}>
    {#snippet title()}
        {editingId === null ? 'Schedule a talk' : 'Event information'}
    {/snippet}
    <form onsubmit={submitEvent} class="space-y-4">
        <p class="text-sm text-muted-foreground">
            {editingId === null
                ? 'Block the date now; you can attach the talk itself later.'
                : 'Rename, reschedule or detach this event.'}
        </p>

        <Input
            label="Name"
            id="event-name"
            bind:value={formName}
            maxlength={255}
            required
            placeholder="e.g. PHP Meetup Tokyo"
            error={formErrors.name}
            data-test="event-name-input"
        />

        <div class="grid grid-cols-2 gap-4">
            <Input
                label="Date"
                id="event-date"
                type="date"
                bind:value={formDate}
                required
                error={formErrors.date}
                data-test="event-date-input"
            />
            <Input
                label="Start time (optional)"
                id="event-time"
                type="time"
                bind:value={formTime}
                error={formErrors.start_time}
                data-test="event-time-input"
            />
        </div>

        <div class="space-y-2">
            <label class="label" for="event-talk">Talk (optional)</label>
            <select
                id="event-talk"
                bind:value={formTalkId}
                class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs transition-colors scheme-light focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none dark:scheme-dark"
                data-test="event-talk-select"
            >
                <option value="">No talk attached yet</option>
                {#each linkableTalks as talk (talk.id)}
                    <option value={String(talk.id)}>
                        {talk.title}
                        {talk.deck_count === 1
                            ? '(1 deck)'
                            : `(${talk.deck_count} decks)`}
                    </option>
                {/each}
            </select>
            {#if formErrors.talk_id}
                <p class="text-sm text-destructive">
                    {formErrors.talk_id}
                </p>
            {/if}
            <p class="text-xs text-muted-foreground">
                Every deck is a talk; its versions stay grouped under it.
            </p>
        </div>

        <div class="modal-action gap-2">
            {#if editingId !== null}
                <Button
                    type="button"
                    variant="ghost"
                    class="mr-auto text-destructive hover:text-destructive"
                    disabled={submitting}
                    onclick={deleteEvent}
                    data-test="event-delete-button"
                >
                    Delete
                </Button>
            {/if}
            {#if formPresentationId !== null}
                <Button
                    type="button"
                    variant="base"
                    outline
                    onclick={() =>
                        router.visit(
                            editPresentation({
                                current_team: teamSlug,
                                presentation: formPresentationId,
                            }).url,
                        )}
                    data-test="event-edit-deck-button"
                >
                    Edit
                </Button>
                <PresentMenu
                    presentationId={formPresentationId}
                    testPrefix="calendar"
                    triggerClass="btn btn-primary"
                    position="top"
                    onNavigate={() => (dialogOpen = false)}
                />
            {/if}
            <Button
                type="submit"
                disabled={submitting || formName.trim() === ''}
                data-test="event-submit-button"
            >
                {editingId === null ? 'Schedule' : 'Save changes'}
            </Button>
        </div>
    </form>
</Modal>
