<script lang="ts">
    import { useHttp } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import RecordReactionsController from '@/actions/App/Http/Controllers/Presentations/RecordReactionsController';
    import SendMessageController from '@/actions/App/Http/Controllers/Presentations/SendMessageController';
    import SendReactionController from '@/actions/App/Http/Controllers/Presentations/SendReactionController';
    import AppHead from '@/components/AppHead.svelte';
    import FloatingReactions from '@/components/tecturn/FloatingReactions.svelte';
    import { getEcho, setPresenceIdentity } from '@/lib/echo';
    import { beaconPost } from '@/lib/tecturn/beacon';

    let {
        presentationName,
        embedToken,
        reactions,
        allowFreeText = false,
        freeTextMaxLength = 20,
    }: {
        presentationName: string;
        embedToken: string;
        // The presentation's reaction set (custom or defaults), from the server.
        reactions: string[];
        // When on, the audience can also send a short free-text message.
        allowFreeText?: boolean;
        freeTextMaxLength?: number;
    } = $props();

    // How often to persist the batched tally, and how often to refresh presence
    // even while idle so the presenter's "watching now" count stays honest.
    const FLUSH_MS = 5000;
    const HEARTBEAT_MS = 12000;

    const http = useHttp({
        emoji: '',
    });

    const messageHttp = useHttp({
        message: '',
    });

    // Separate channel for the debounced, persisted tally so it never competes
    // with the instant broadcast above.
    const batch = useHttp<{
        viewerId: string;
        counts: Record<string, number>;
        leaving: boolean;
    }>({
        viewerId: '',
        counts: {},
        leaving: false,
    });

    const batchUrl = RecordReactionsController.url({
        presentation: embedToken,
    });

    let floatingReactions = $state<FloatingReactions>();
    let flaring = $state<string | null>(null);
    let lastSentAt = 0;
    let messageDraft = $state('');

    let viewerId = '';
    // Reactions accumulate here between flushes instead of hitting the server
    // on every tap.
    let pending: Record<string, number> = {};
    let lastFlushAt = 0;

    function sendReaction(emoji: string): void {
        const now = performance.now();

        if (now - lastSentAt < 250) {
            return;
        }

        lastSentAt = now;

        floatingReactions?.spawnReaction(emoji);
        flaring = emoji;
        setTimeout(() => {
            if (flaring === emoji) {
                flaring = null;
            }
        }, 400);

        // Instant broadcast so it lands on the big screen right away.
        http.emoji = emoji;
        http.post(SendReactionController.url({ presentation: embedToken }));

        // Tally locally; the batch flush persists it.
        pending[emoji] = (pending[emoji] ?? 0) + 1;
    }

    function sendMessage(): void {
        const text = messageDraft.trim();

        if (!allowFreeText || text === '') {
            return;
        }

        const now = performance.now();

        if (now - lastSentAt < 250) {
            return;
        }

        lastSentAt = now;
        messageDraft = '';

        // Float it on the sender's own screen, then broadcast to the hall.
        floatingReactions?.spawnMessage(text);
        messageHttp.message = text;
        messageHttp.post(SendMessageController.url({ presentation: embedToken }));
    }

    function flush(): void {
        const hasReactions = Object.keys(pending).length > 0;
        const heartbeatDue = performance.now() - lastFlushAt >= HEARTBEAT_MS;

        if (!hasReactions && !heartbeatDue) {
            return;
        }

        const counts = { ...pending };
        pending = {};
        lastFlushAt = performance.now();

        batch.viewerId = viewerId;
        batch.counts = counts;
        batch.leaving = false;
        batch.post(batchUrl);
    }

    onMount(() => {
        const key = 'tecturn:viewerId';
        viewerId = sessionStorage.getItem(key) ?? crypto.randomUUID();
        sessionStorage.setItem(key, viewerId);

        // Join the live presence channel: simply being a member is what the
        // presenter counts as "watching now". When this tab closes, Reverb
        // drops the member automatically, so the count falls without relying
        // on a farewell request landing.
        const presenceChannel = `presentation-live.${embedToken}`;
        setPresenceIdentity(viewerId);
        getEcho().join(presenceChannel);

        // The heartbeat below only persists analytics (unique viewers,
        // reactions); the live count no longer depends on it.
        lastFlushAt = 0;
        flush();

        const interval = setInterval(flush, FLUSH_MS);

        const leave = (): void => {
            beaconPost(batchUrl, {
                viewerId,
                leaving: true,
                counts: { ...pending },
            });
            pending = {};
        };

        window.addEventListener('pagehide', leave);

        return () => {
            clearInterval(interval);
            window.removeEventListener('pagehide', leave);
            getEcho().leave(presenceChannel);
            leave();
        };
    });
</script>

<AppHead title={presentationName} />

<!-- The guest's own reactions float up their screen, mirroring the hall. -->
<div class="pointer-events-none absolute inset-0 z-20" aria-hidden="true">
    <FloatingReactions bind:this={floatingReactions} enabled />
</div>

<main
    class="relative z-10 flex flex-1 flex-col items-center px-6 pt-[18dvh] pb-[max(2.5rem,env(safe-area-inset-bottom))]"
>
    <header class="flex flex-col items-center text-center">
        <p
            class="flex items-center gap-2 font-mono text-[11px] font-semibold tracking-[0.3em] text-[hsl(37_6%_55%)] uppercase"
        >
            <span
                class="live-dot h-2 w-2 rounded-full bg-live"
                aria-hidden="true"
            ></span>
            Live
        </p>
        <p class="mt-6 text-sm text-[hsl(37_6%_55%)]">
            You're in the audience of
        </p>
        <h1 class="font-display mt-2 max-w-md text-3xl font-bold text-balance">
            {presentationName}
        </h1>
    </header>

    <div class="flex-1"></div>

    <p class="mb-6 text-sm text-[hsl(37_6%_55%)]">
        Tap a key — it lands on the big screen
    </p>

    <div class="grid grid-cols-3 gap-4">
        {#each reactions as emoji (emoji)}
            <button
                type="button"
                class="footlight-key flex h-20 w-20 items-center justify-center rounded-full text-4xl select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[hsl(37_91%_55%)]"
                class:is-flaring={flaring === emoji}
                onclick={() => sendReaction(emoji)}
                aria-label="React with {emoji}"
            >
                {emoji}
            </button>
        {/each}
    </div>

    {#if allowFreeText}
        <form
            class="mt-6 flex w-full max-w-sm items-center gap-2"
            onsubmit={(event) => {
                event.preventDefault();
                sendMessage();
            }}
        >
            <input
                type="text"
                bind:value={messageDraft}
                maxlength={freeTextMaxLength}
                placeholder="Say something…"
                aria-label="Send a message to the screen"
                class="footlight-input min-w-0 flex-1 rounded-full px-4 py-2.5 text-sm text-white placeholder:text-[hsl(37_6%_45%)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[hsl(37_91%_55%)]"
            />
            <button
                type="submit"
                disabled={messageDraft.trim() === ''}
                class="footlight-key flex h-11 shrink-0 items-center justify-center rounded-full px-4 text-sm font-semibold text-white disabled:opacity-40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[hsl(37_91%_55%)]"
            >
                Send
            </button>
        </form>
    {/if}
</main>

<style>
    .footlight-input {
        background-color: hsl(34 10% 15%);
        box-shadow:
            inset 0 1px 0 hsl(40 20% 92% / 0.06),
            0 0 0 1px hsl(34 9% 21%);
    }

    .footlight-key {
        background-color: hsl(34 10% 15%);
        box-shadow:
            inset 0 1px 0 hsl(40 20% 92% / 0.06),
            0 0 0 1px hsl(34 9% 21%),
            0 8px 24px -12px hsl(36 45% 4% / 0.8);
        transition:
            transform 120ms ease,
            box-shadow 200ms ease;
    }

    .footlight-key:active {
        transform: scale(0.92);
    }

    .footlight-key.is-flaring {
        box-shadow:
            inset 0 1px 0 hsl(40 20% 92% / 0.06),
            0 0 0 1.5px hsl(37 91% 55%),
            0 0 28px hsl(37 91% 55% / 0.35);
    }

    @keyframes live-pulse {
        0%,
        100% {
            opacity: 1;
        }
        50% {
            opacity: 0.35;
        }
    }

    .live-dot {
        animation: live-pulse 2.4s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .live-dot {
            animation: none;
        }

        .footlight-key,
        .footlight-key:active {
            transition: none;
            transform: none;
        }
    }
</style>
