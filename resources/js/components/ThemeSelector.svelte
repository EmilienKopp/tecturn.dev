<script lang="ts">
    import { themeState, THEME_OPTIONS } from '@/lib/theme.svelte';
    import type { ThemeOption } from '@/lib/theme.svelte';

    /**
     * Uncontrolled by default: selects and switches the app theme. Pass
     * `value` + `onSelect` to use it as a plain picker (e.g. choosing a
     * palette source) without touching the app theme.
     */
    let {
        options = THEME_OPTIONS,
        value,
        onSelect,
    }: {
        options?: ThemeOption[];
        value?: string;
        onSelect?: (name: string) => void;
    } = $props();

    const { theme, updateTheme } = themeState();

    const selected = $derived(value ?? theme.value);

    function select(name: string): void {
        if (onSelect) {
            onSelect(name);
        } else {
            updateTheme(name);
        }
    }

    const previewName = (name: string): string =>
        name === 'auto' ? 'onair' : name;
</script>

<div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
    {#each options as option (option.name)}
        <button
            type="button"
            data-theme={previewName(option.name)}
            onclick={() => select(option.name)}
            class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-left text-sm transition-shadow hover:shadow-sm {selected ===
            option.name
                ? 'ring-primary ring-2'
                : ''}"
            style="background: var(--color-base-100); color: var(--color-base-content); border-color: var(--color-base-300);"
            aria-pressed={selected === option.name}
        >
            <span class="truncate">
                {option.label}
            </span>
            <span class="flex shrink-0 gap-1">
                <span
                    class="h-3 w-1.5 rounded-full"
                    style="background: var(--color-primary)"
                ></span>
                <span
                    class="h-3 w-1.5 rounded-full"
                    style="background: var(--color-secondary)"
                ></span>
                <span
                    class="h-3 w-1.5 rounded-full"
                    style="background: var(--color-accent)"
                ></span>
                <span
                    class="h-3 w-1.5 rounded-full"
                    style="background: var(--color-neutral)"
                ></span>
            </span>
        </button>
    {/each}
</div>
