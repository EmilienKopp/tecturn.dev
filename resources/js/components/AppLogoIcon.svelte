<script lang="ts">
    let {
        class: className = '',
        ...rest
    }: {
        class?: string;
        [key: string]: unknown;
    } = $props();
</script>

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 2000 2000"
    class={className}
    {...rest}
>
    <path fill="currentColor" d="M206 468h586v130H573v628H425V598H206z" />
    <path
        fill="currentColor"
        d="M206 468h586v130H573v628H425V598H206z"
        transform="rotate(180 1000 1000)"
    />
    <circle cx="1000" cy="1000" r="91" fill="#f80131" />
</svg>
