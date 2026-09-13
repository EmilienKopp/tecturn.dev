/**
 * Tests for the editor draft store (resources/js/lib/tecturn/editor-draft.ts).
 *
 * Run with: npm run test:js
 *
 * Node strips the TypeScript on import natively; the type-only import of
 * generated types is erased, so no bundler or alias resolution is involved. A
 * minimal in-memory localStorage stub stands in for the browser global.
 */
import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';

class MemoryStorage {
    #store = new Map();

    getItem(key) {
        return this.#store.has(key) ? this.#store.get(key) : null;
    }

    setItem(key, value) {
        this.#store.set(key, String(value));
    }

    removeItem(key) {
        this.#store.delete(key);
    }
}

globalThis.localStorage = new MemoryStorage();

const {
    clearEditorDraft,
    isDraftNewer,
    loadEditorDraft,
    saveEditorDraft,
} = await import('../resources/js/lib/tecturn/editor-draft.ts');

const sampleDraft = () => ({
    name: 'My deck',
    content: { version: '1', slides: [], backgroundImage: null },
    flow: { version: '1', nodes: [], edges: [] },
    selectedSlideIndex: 0,
    selectedBlockId: null,
});

beforeEach(() => {
    globalThis.localStorage = new MemoryStorage();
});

test('loadEditorDraft returns null when nothing is stored', () => {
    assert.equal(loadEditorDraft(1), null);
});

test('saveEditorDraft round-trips and stamps savedAt', () => {
    const before = Date.now();
    saveEditorDraft(7, sampleDraft());
    const loaded = loadEditorDraft(7);

    assert.equal(loaded.name, 'My deck');
    assert.deepEqual(loaded.content.slides, []);
    assert.ok(loaded.savedAt >= before);
});

test('drafts are scoped per presentation id', () => {
    saveEditorDraft(1, { ...sampleDraft(), name: 'One' });
    saveEditorDraft(2, { ...sampleDraft(), name: 'Two' });

    assert.equal(loadEditorDraft(1).name, 'One');
    assert.equal(loadEditorDraft(2).name, 'Two');
});

test('clearEditorDraft removes only the target draft', () => {
    saveEditorDraft(1, sampleDraft());
    saveEditorDraft(2, sampleDraft());
    clearEditorDraft(1);

    assert.equal(loadEditorDraft(1), null);
    assert.notEqual(loadEditorDraft(2), null);
});

test('loadEditorDraft returns null on corrupt JSON', () => {
    globalThis.localStorage.setItem('tecturn-editor-draft-3', '{not json');

    assert.equal(loadEditorDraft(3), null);
});

test('isDraftNewer is true when the deck was never saved', () => {
    assert.equal(isDraftNewer({ savedAt: 1000 }, null), true);
});

test('isDraftNewer compares against the server updated_at', () => {
    const serverTime = '2026-09-13T10:00:00.000Z';
    const serverMs = Date.parse(serverTime);

    assert.equal(isDraftNewer({ savedAt: serverMs + 1000 }, serverTime), true);
    assert.equal(isDraftNewer({ savedAt: serverMs - 1000 }, serverTime), false);
});
