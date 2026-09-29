<script lang="ts">
    import { highlight } from '@/lib/tecturn/shiki';

    let {
        value,
        lang,
        oninput,
        onblur,
        autofocus = false,
        class: className = '',
    }: {
        value: string;
        lang: string;
        oninput: (value: string) => void;
        onblur?: () => void;
        autofocus?: boolean;
        class?: string;
    } = $props();

    let highlightedHtml = $state('');
    let textareaEl = $state<HTMLTextAreaElement | null>(null);
    let highlightEl = $state<HTMLDivElement | null>(null);

    $effect(() => {
        void highlight(value || '// start typing…', lang).then((html) => {
            highlightedHtml = html;
        });
    });

    $effect(() => {
        if (autofocus && textareaEl) {
            textareaEl.focus();
        }
    });

    // The textarea is the only scrollable layer; keep the painted mirror in
    // lockstep so the caret never drifts away from the highlighted text.
    function syncScroll() {
        if (textareaEl && highlightEl) {
            highlightEl.scrollTop = textareaEl.scrollTop;
            highlightEl.scrollLeft = textareaEl.scrollLeft;
        }
    }
</script>

<!-- Transparent textarea over a shiki-highlighted mirror: both layers share
     the same font metrics and padding so the caret lines up with the paint. -->
<div class="relative min-h-16 w-full overflow-hidden rounded-md {className}">
    <textarea
        bind:this={textareaEl}
        class="absolute inset-0 h-full w-full resize-none overflow-auto whitespace-pre bg-transparent p-4 font-mono text-sm leading-6 text-transparent caret-white outline-none"
        style="z-index: 1; color: transparent; tab-size: 4;"
        {value}
        wrap="off"
        oninput={(event) => oninput(event.currentTarget.value)}
        onscroll={syncScroll}
        {onblur}
        spellcheck="false"
        autocomplete="off"
    ></textarea>
    <div
        bind:this={highlightEl}
        class="pointer-events-none h-full select-none overflow-hidden [&>pre]:m-0 [&>pre]:h-full [&>pre]:min-h-16 [&>pre]:whitespace-pre [&>pre]:rounded-md [&>pre]:p-4 [&>pre]:font-mono [&>pre]:text-sm [&>pre]:leading-6"
        style="z-index: 0; tab-size: 4;"
    >
        <!-- eslint-disable-next-line svelte/no-at-html-tags -->
        {@html highlightedHtml}
    </div>
</div>
