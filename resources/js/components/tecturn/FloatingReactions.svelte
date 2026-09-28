<script lang="ts">
    let { enabled = false }: { enabled?: boolean } = $props();

    // A particle is either an emoji (short float) or a free-text message
    // (readable chip that floats a touch longer so it can be read).
    type Particle = {
        id: number;
        x: number;
        emoji?: string;
        text?: string;
    };

    let particles = $state<Particle[]>([]);
    let counter = 0;

    function spawn(
        particle: Omit<Particle, 'id' | 'x'>,
        lifetimeMs: number,
    ): void {
        if (!enabled) {
            return;
        }

        const id = ++counter;
        const x = 10 + Math.random() * 80;

        particles = [...particles, { id, x, ...particle }];

        setTimeout(() => {
            particles = particles.filter((p) => p.id !== id);
        }, lifetimeMs);
    }

    export function spawnReaction(emoji: string): void {
        spawn({ emoji }, 3000);
    }

    export function spawnMessage(text: string): void {
        spawn({ text }, 5000);
    }
</script>

{#if enabled}
    <div
        class="pointer-events-none absolute inset-0 z-[9999] overflow-hidden"
        aria-hidden="true"
    >
        {#each particles as particle (particle.id)}
            {#if particle.text}
                <span
                    class="float-message absolute bottom-0 max-w-[80%] -translate-x-1/2 rounded-full bg-black/80 px-4 py-2 text-lg font-semibold whitespace-nowrap text-white shadow-lg ring-1 ring-white/15"
                    style="left: {particle.x}%"
                >
                    {particle.text}
                </span>
            {:else}
                <span
                    class="float-emoji absolute bottom-0 select-none text-4xl"
                    style="left: {particle.x}%"
                >
                    {particle.emoji}
                </span>
            {/if}
        {/each}
    </div>
{/if}

<style>
    @keyframes float-up {
        0% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        80% {
            opacity: 0.8;
        }
        100% {
            transform: translateY(-80vh) scale(1.4);
            opacity: 0;
        }
    }

    /* Text keeps a steady scale so it stays legible while it rises; the
       -translate-x-1/2 in the class keeps the chip centred on its x point. */
    @keyframes float-up-message {
        0% {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        80% {
            opacity: 0.9;
        }
        100% {
            transform: translateX(-50%) translateY(-80vh);
            opacity: 0;
        }
    }

    .float-emoji {
        animation: float-up 3s ease-out forwards;
    }

    .float-message {
        animation: float-up-message 5s ease-out forwards;
    }

    @media (prefers-reduced-motion: reduce) {
        .float-emoji,
        .float-message {
            animation: none;
            opacity: 0;
        }
    }
</style>
