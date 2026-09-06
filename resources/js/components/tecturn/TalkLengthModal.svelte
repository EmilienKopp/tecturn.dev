<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import {
        Dialog,
        DialogContent,
        DialogDescription,
        DialogFooter,
        DialogTitle,
    } from '@/components/ui/dialog';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';

    let {
        durationMinutes,
        timerMode,
        open = $bindable(false),
        onSave,
    }: {
        durationMinutes: number | null;
        timerMode: string;
        open?: boolean;
        onSave: (next: {
            durationMinutes: number | null;
            timerMode: string;
        }) => void;
    } = $props();

    // Staged edits — only committed on Save.
    let hasTarget = $state(false);
    let minutes = $state(20);
    let countdown = $state(false);

    function seed() {
        hasTarget = durationMinutes !== null && durationMinutes > 0;
        minutes = hasTarget ? (durationMinutes as number) : 20;
        countdown = timerMode === 'countdown';
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

    function handleOpenChange(value: boolean) {
        open = value;
    }

    function save() {
        const clamped = Math.min(480, Math.max(1, Math.round(minutes || 0)));

        onSave({
            durationMinutes: hasTarget ? clamped : null,
            timerMode: countdown ? 'countdown' : 'elapsed',
        });
        open = false;
    }
</script>

<Dialog {open} onOpenChange={handleOpenChange}>
    <DialogContent class="sm:max-w-md">
        <div class="space-y-3">
            <DialogTitle>Talk length</DialogTitle>
            <DialogDescription>
                Set the ideal length of your talk. The editor uses it to check
                pacing, and the presenter timer paces each slide against it.
            </DialogDescription>
        </div>

        <div class="grid gap-4">
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

            <div class="grid gap-2">
                <Label for="talk-minutes">Target (minutes)</Label>
                <Input
                    id="talk-minutes"
                    type="number"
                    min="1"
                    max="480"
                    bind:value={minutes}
                    disabled={!hasTarget}
                    data-test="talk-minutes"
                />
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

        <DialogFooter class="gap-2">
            <Button variant="secondary" onclick={() => (open = false)}>
                Cancel
            </Button>
            <Button onclick={save} data-test="talk-length-save">Save</Button>
        </DialogFooter>
    </DialogContent>
</Dialog>
