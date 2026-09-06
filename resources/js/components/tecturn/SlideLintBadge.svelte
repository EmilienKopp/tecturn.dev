<script lang="ts">
    import { formatSpeakingTime } from '@/lib/tecturn/CodeGeneration/lint';
    import type { SlideLint } from '@/lib/tecturn/CodeGeneration/lint';

    let {
        lint,
        showLabel = true,
    }: {
        lint: SlideLint;
        showLabel?: boolean;
    } = $props();

    // Amber is the theme's one "pay attention" colour; green stays quiet so a
    // healthy slide never nags, red is the only alarm.
    const dotClass = $derived(
        lint.verdict === 'over'
            ? 'bg-red-500'
            : lint.verdict === 'warn'
              ? 'bg-amber-500'
              : 'bg-emerald-500/70',
    );

    const countLabel = $derived(
        lint.dominantScript === 'cjk'
            ? `${lint.cjkChars} chars`
            : `${lint.words} words`,
    );

    const title = $derived(
        `${lint.words ? `${lint.words} words` : ''}${
            lint.words && lint.cjkChars ? ', ' : ''
        }${lint.cjkChars ? `${lint.cjkChars} chars` : ''} · ~${formatSpeakingTime(
            lint.speakingSeconds,
        )} to say`,
    );
</script>

<span
    class="flex items-center gap-1 text-[10px] text-muted-foreground"
    {title}
    data-test="slide-lint-badge"
    data-verdict={lint.verdict}
>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {dotClass}"></span>
    {#if showLabel}
        <span class="truncate">
            {countLabel} · ~{formatSpeakingTime(lint.speakingSeconds)}
        </span>
    {/if}
</span>
