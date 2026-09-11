# Edge WS Broker (Cloudflare Durable Objects) — design note

**Status: exploration / "someday". Nothing here is built.** This captures the shape of a
future direction so we don't have to re-derive it.

## The question

Can an exported presentation live "closer to users" (e.g. a Cloudflare edge worker), and
what does that mean for our Reverb WebSocket channels?

## First, separate two things

"Put it on the edge" blurs two very different problems:

1. **The deck itself** — the export bundle (HTML/JS/CSS, inlined fonts, rendered code blocks)
   is pure static content. Serving it from a Worker or Pages is basically free and needs
   **zero WS changes**. This is really just "put it on a CDN": helps first paint and asset
   delivery, nothing more.
2. **The live layer** — presence, live viewer count, reactions, presenter-drives-audience
   sync, translation events (see session-analytics-presence + yoyotranslate notes). This is
   inherently a **hub-and-spoke** problem. A WebSocket needs one stateful place to fan
   messages out to. Edge workers are stateless and spread out by design, the opposite of
   what a coordination hub wants.

Key point: **WS latency is about proximity to the coordination hub, not to the static
assets.** Moving the deck to the edge does not speed up reactions/sync unless you also move
the hub, and moving the hub only helps if participants are geographically dispersed. For a
co-located room (classroom, conference), a central Reverb is already fine.

## The good version: a Durable Object as WS broker + write-behind buffer

Rather than "run the deck at the edge", the worthwhile idea is: **one Durable Object per
session** acts as the room's WS broker and buffers analytics, flushing coalesced batches
back to Laravel for persistence.

This fits what we already do. We already batch reactions/viewer data into
`presentation_sessions` instead of writing per-event. The DO just moves that buffer from the
app process to the edge. Laravel goes from a flood of events to a trickle of batches.

It also maps onto our CQRS write path. The DO is effectively a write-side aggregate for the
live session: hot state in the DO, periodic snapshot to the repository via one Action taking
a batch Command.

```
edge DO (room hub + buffer)  --HTTP POST batch-->  Controller -> Action -> Repository -> tables
```

### What the DO is genuinely good at

- **Fan-out**: one DO = the room. Reactions, presence, sync all broadcast from it.
- **Authoritative live state**: "current slide", "who's connected" live naturally in a
  single-instance actor. Cleaner than Reverb, which is stateless and has nowhere to keep it.
- **Buffering + flush**: use the **Alarms API** to flush every N seconds, plus flush on a
  size threshold, plus **flush on session end** (DO detects the last WS closing). Don't rely
  on the interval alone or you lose the tail of a session.

## Gotchas that actually matter

1. **Durability before flush.** A DO has transactional storage, not just memory. Anything
   that must survive eviction/crash gets written to DO storage on ingest, not just an
   in-memory array. Throwaway metrics (live count) can stay in memory. Decide per-signal.
2. **Idempotent flushes.** A retry after a network hiccup must not double-count. Stamp each
   batch with an id; the Laravel side dedupes/upserts.
3. **Auth in both directions.** Clients joining the DO: Laravel issues a short-lived signed
   token at load, the DO verifies it (no per-connection callback). DO → Laravel flush: shared
   secret / signed request on an internal endpoint.
4. **Authority inversion.** This is the real conceptual shift. Today Laravel/Reverb is
   authoritative. With a DO in front, the DO becomes the source of truth for live session
   state and Laravel becomes the downstream persistence sink. Coherent (hot path at edge,
   cold store at origin), but a genuine fork in the model — adopt it deliberately.

## Where Reverb lands

Don't keep both as the session hub. Clean split:

- **Audience/session real-time → the DO** (reactions, presence, sync).
- **Presenter's in-app authenticated real-time → keep Reverb** (or have that dashboard also
  read from the DO). You don't have to rip Reverb out of the whole app to adopt this for the
  session path.

The contract — channel names, event payloads, the flush batch shape — should be owned by the
single-source codegen pipeline so the DO's TS and Laravel's PHP don't drift.

## Adoption tiers (independent, pick as needed)

1. **Static deck on the edge, WS still to origin Reverb.** Cheap, no rewrite. Deck loads
   fast everywhere; real-time hits the central server. Probably enough for live presentations.
2. **Standalone/offline export.** Bundle runs self-contained, live features simply absent or
   degrading gracefully. Good for "here's the deck, no server needed" distribution.
3. **DO as broker + buffer (this note).** Worth it for the buffering/absorption win almost
   regardless of geography. The *latency* win only shows up for globally-dispersed audiences.
