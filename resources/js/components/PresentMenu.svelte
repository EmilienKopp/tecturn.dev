<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import ChevronDown from 'lucide-svelte/icons/chevron-down';
    import FlaskConical from 'lucide-svelte/icons/flask-conical';
    import Play from 'lucide-svelte/icons/play';
    import Timer from 'lucide-svelte/icons/timer';
    import { present } from '@/routes/presentations';
    import { Dropdown } from 'daisy-svelte';

    let {
        presentationId,
        testPrefix = 'present',
        triggerClass = 'btn btn-primary btn-sm shadow',
        align = 'end',
        position = 'bottom',
        onNavigate,
    }: {
        presentationId: number;
        testPrefix?: string;
        triggerClass?: string;
        align?: 'start' | 'end';
        position?: 'bottom' | 'top';
        // Called after a mode is picked, e.g. to close a surrounding modal.
        onNavigate?: () => void;
    } = $props();

    const presentUrl = $derived(
        page.props.currentTeam
            ? present({
                  current_team: page.props.currentTeam.slug,
                  presentation: presentationId,
              }).url
            : null,
    );

    // Same present screen, but `test=1` tells the presenter to skip opening an
    // analytics session so a test run never pollutes the numbers.
    const testRunUrl = $derived(presentUrl ? `${presentUrl}?test=1` : null);

    // Rehearsal mode: same screen again, but with a start/stop rehearsal timer
    // whose runs are saved with a snapshot of the deck.
    const rehearsalUrl = $derived(
        presentUrl ? `${presentUrl}?rehearsal=1` : null,
    );
</script>

{#if presentUrl}
    <Dropdown {align} {position} class="w-56">
        {#snippet trigger()}
            <span class={triggerClass} data-test="{testPrefix}-present-menu">
                <Play class="h-4 w-4" /> Present
                <ChevronDown class="h-3.5 w-3.5 opacity-60" />
            </span>
        {/snippet}
        {#snippet children({ close })}
            <li>
                <a
                    class="gap-2"
                    onclick={() => {
                        close();
                        onNavigate?.();
                    }}
                    href={presentUrl}
                    target="_blank"
                    rel="noopener"
                    data-test="{testPrefix}-present-link"
                >
                    <Play class="h-4 w-4" />Go Live
                </a>
            </li>
            <li>
                <a
                    class="gap-2"
                    onclick={() => {
                        close();
                        onNavigate?.();
                    }}
                    href={testRunUrl}
                    target="_blank"
                    rel="noopener"
                    data-test="{testPrefix}-test-run-link"
                >
                    <FlaskConical class="h-4 w-4" />Test run
                </a>
            </li>
            <li>
                <a
                    class="gap-2"
                    onclick={() => {
                        close();
                        onNavigate?.();
                    }}
                    href={rehearsalUrl}
                    target="_blank"
                    rel="noopener"
                    data-test="{testPrefix}-rehearse-link"
                >
                    <Timer class="h-4 w-4" />Rehearse
                </a>
            </li>
        {/snippet}
    </Dropdown>
{/if}
