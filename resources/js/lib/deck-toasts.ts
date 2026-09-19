import { page, router } from '@inertiajs/svelte';
import { toast } from 'svelte-sonner';
import { getEcho } from '@/lib/echo';

type DeckGeneratedPayload = {
    id: number;
    name: string;
    url: string;
};

/**
 * If the user is looking at the presentations index, refresh the list and the
 * "building…" skeleton count so the finished deck replaces its skeleton.
 */
function refreshPresentationsIndex(): void {
    if (page.component !== 'presentations/Index') {
        return;
    }

    router.reload({ only: ['presentations', 'generatingCount'] });
}

/**
 * Subscribe the current user's private channel to deck-generation outcomes and
 * toast them. Returns a cleanup function that leaves the channel.
 */
export function subscribeDeckToasts(userId: number): () => void {
    const channelName = `App.Models.User.${userId}`;

    getEcho()
        .private(channelName)
        .listen('.deck.generated', (payload: DeckGeneratedPayload) => {
            toast.success(`"${payload.name}" is ready`, {
                action: {
                    label: 'Open',
                    onClick: () => router.visit(payload.url),
                },
            });
            refreshPresentationsIndex();
        })
        .listen('.deck.generation.failed', () => {
            toast.error(
                "We couldn't build your deck. Please try again or refine your outline.",
            );
            refreshPresentationsIndex();
        });

    return () => {
        getEcho().leave(channelName);
    };
}
