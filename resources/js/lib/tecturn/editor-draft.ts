import type { FlowGraph, PresentationContent } from '@/types/generated';

/**
 * Browser-local snapshot of an in-progress editing session. Persisted to
 * localStorage on every edit (independent of the server auto-save toggle) so a
 * reload or crash never loses unsaved work.
 */
export interface EditorDraft {
    /** Epoch milliseconds when the draft was written; compared to the deck's server `updated_at`. */
    savedAt: number;
    name: string;
    content: PresentationContent;
    flow: FlowGraph;
    selectedSlideIndex: number;
    selectedBlockId: string | null;
}

const keyFor = (presentationId: number): string =>
    `tecturn-editor-draft-${presentationId}`;

export function loadEditorDraft(presentationId: number): EditorDraft | null {
    try {
        const stored = localStorage.getItem(keyFor(presentationId));

        if (!stored) {
            return null;
        }

        return JSON.parse(stored) as EditorDraft;
    } catch {
        return null;
    }
}

export function saveEditorDraft(
    presentationId: number,
    draft: Omit<EditorDraft, 'savedAt'>,
): void {
    try {
        localStorage.setItem(
            keyFor(presentationId),
            JSON.stringify({
                ...draft,
                savedAt: Date.now(),
            } satisfies EditorDraft),
        );
    } catch {
        // Ignore quota or serialization errors; persistence is best-effort.
    }
}

export function clearEditorDraft(presentationId: number): void {
    try {
        localStorage.removeItem(keyFor(presentationId));
    } catch {
        // Ignore storage errors.
    }
}

/**
 * A draft is worth restoring only when it holds edits the server hasn't seen:
 * either the deck was never saved, or the draft was written after the last
 * server save.
 */
export function isDraftNewer(
    draft: EditorDraft,
    serverUpdatedAt: string | null,
): boolean {
    if (serverUpdatedAt === null) {
        return true;
    }

    return draft.savedAt > Date.parse(serverUpdatedAt);
}
