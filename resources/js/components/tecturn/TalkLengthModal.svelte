<script lang="ts">
    import { Button, Checkbox, Input, Modal } from 'daisy-svelte';
    import { formatSpeakingTime } from '@/lib/tecturn/CodeGeneration/lint';
    import type { DeliveryStats } from '@/types/generated';

    let {
        durationMinutes,
        timerMode,
        presentationStyle,
        deliveryStats,
        deckWords,
        open = $bindable(false),
        onSave,
    }: {
        durationMinutes: number | null;
        timerMode: string;
        presentationStyle: string;
        deliveryStats: DeliveryStats;
        deckWords: number;
        open?: boolean;
        onSave: (next: {
            durationMinutes: number | null;
            timerMode: string;
            presentationStyle: string;
        }) => void;
    } = $props();

    // What your own runs say, next to what the linter guesses. Words per
    // minute divides today's deck text by the measured average run, so it
    // shifts as you edit — that's the point: it shows which style multiplier
    // your delivery actually resembles.
    const measuredRuns = $derived(
        deliveryStats.rehearsalCount + deliveryStats.sessionCount,
    );
    const measuredWpm = $derived(
        deliveryStats.avgRunSeconds && deckWords > 0
            ? Math.round(deckWords / (deliveryStats.avgRunSeconds / 60))
            : null,
    );

    // How much of the on-screen text you narrate verbatim. Drives the
    // speaking-time estimate: the linter's raw count assumes every word is
    // read aloud, which only holds for text-heavy decks.
    const STYLES = [
        {
            value: 'minimalistic',
            label: 'Minimalistic',
            hint: 'Sparse slides, you do the talking (~6× the read-aloud time).',
        },
        {
            value: 'balanced',
            label: 'Balanced',
            hint: 'Keywords and short lines you expand on (~3×).',
        },
        {
            value: 'text-heavy',
            label: 'Text-heavy',
            hint: 'Most of the text is spoken as written (1×).',
        },
    ];

    // Staged edits — only committed on Save.
    let hasTarget = $state(false);
    let minutes = $state(20);
    let countdown = $state(false);
    let style = $state('balanced');

    function seed() {
        hasTarget = durationMinutes !== null && durationMinutes > 0;
        minutes = hasTarget ? (durationMinutes as number) : 20;
        countdown = timerMode === 'countdown';
        style = presentationStyle;
    }

    // Re-seed whenever the modal opens (the trigger sets `open` directly, which
    // bypasses onOpenChange), mirroring FooterSettingsModal.
    let wasOpen = false;

    $effect(() => {
        if (open && !wasOpen) {
            seed();
        }

        wasOpen = open;
    });

    function save() {
        const clamped = Math.min(480, Math.max(1, Math.round(minutes || 0)));

        onSave({
            durationMinutes: hasTarget ? clamped : null,
            timerMode: countdown ? 'countdown' : 'elapsed',
            presentationStyle: style,
        });
        open = false;
    }
</script>

<Modal bind:open class="sm:max-w-md">
    {#snippet title()}Talk length{/snippet}
    <p class="text-muted-foreground text-sm">
        Set the ideal length of your talk. The editor uses it to check pacing,
        and the presenter timer paces each slide against it.
    </p>

    <div class="mt-4 grid gap-4">
            <label
                class="flex items-center gap-2 text-sm"
                for="talk-has-target"
            >
                <Checkbox
                    id="talk-has-target"
                    bind:checked={hasTarget}
                    data-test="talk-has-target"
                />
                Set a target duration
            </label>

            <Input
                label="Target (minutes)"
                id="talk-minutes"
                type="number"
                min="1"
                max="480"
                bind:value={minutes}
                disabled={!hasTarget}
                data-test="talk-minutes"
            />

            {#if measuredRuns > 0}
                <div
                    class="space-y-1.5 rounded-md border p-2.5"
                    data-test="talk-delivery-stats"
                >
                    <p class="text-xs font-medium">
                        Measured from {deliveryStats.rehearsalCount} rehearsal{deliveryStats.rehearsalCount ===
                        1
                            ? ''
                            : 's'}{deliveryStats.sessionCount > 0
                            ? ` and ${deliveryStats.sessionCount} live session${
                                  deliveryStats.sessionCount === 1 ? '' : 's'
                              }`
                            : ''}
                    </p>
                    <div
                        class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-muted-foreground"
                    >
                        {#if deliveryStats.avgRunSeconds}
                            <span>Average run</span>
                            <span class="text-right font-mono tabular-nums">
                                {formatSpeakingTime(
                                    deliveryStats.avgRunSeconds,
                                )}
                            </span>
                        {/if}
                        {#if deliveryStats.avgSecondsPerSlide}
                            <span>Average per slide</span>
                            <span class="text-right font-mono tabular-nums">
                                {formatSpeakingTime(
                                    deliveryStats.avgSecondsPerSlide,
                                )}
                            </span>
                        {/if}
                        {#if measuredWpm}
                            <span>Your pace on this deck's text</span>
                            <span class="text-right font-mono tabular-nums">
                                ~{measuredWpm} words/min
                            </span>
                        {/if}
                    </div>
                </div>
            {/if}

            <div class="grid gap-2">
                <span class="label">Presentation style</span>
                <div class="grid gap-1.5">
                    {#each STYLES as option (option.value)}
                        <label
                            class="flex cursor-pointer items-start gap-2 rounded-md border p-2.5 text-sm transition-colors {style ===
                            option.value
                                ? 'border-primary bg-accent'
                                : 'hover:bg-accent/50'}"
                        >
                            <input
                                type="radio"
                                name="presentation-style"
                                value={option.value}
                                bind:group={style}
                                class="mt-0.5"
                                data-test="talk-style-{option.value}"
                            />
                            <span>
                                {option.label}
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {option.hint}
                                </span>
                            </span>
                        </label>
                    {/each}
                </div>
                <p class="text-xs text-muted-foreground">
                    Sets how the editor turns your slide text into a
                    speaking-time estimate.
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm" for="talk-countdown">
                <Checkbox
                    id="talk-countdown"
                    bind:checked={countdown}
                    disabled={!hasTarget}
                    data-test="talk-countdown"
                />
                <span>
                    Count down during the talk
                    <span class="block text-xs text-muted-foreground">
                        Timer shows time left instead of time elapsed.
                    </span>
                </span>
            </label>
        </div>

    {#snippet actions()}
        <Button variant="secondary" onclick={() => (open = false)}>
            Cancel
        </Button>
        <Button onclick={save} data-test="talk-length-save">Save</Button>
    {/snippet}
</Modal>
