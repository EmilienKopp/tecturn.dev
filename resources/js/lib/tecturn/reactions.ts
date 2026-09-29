/**
 * Reaction defaults, mirroring TalkSettings::DEFAULT_REACTIONS and
 * TalkSettings::MAX_REACTIONS on the PHP side. Used to seed the editor when a
 * presentation has no custom set yet and to bound the customiser.
 */
export const DEFAULT_REACTIONS = ['👏', '❤️', '😂', '🤯', '🙌', '🔥'];

export const MAX_REACTIONS = 10;

/** Mirrors TalkSettings::MAX_FREE_TEXT_LENGTH. */
export const MAX_FREE_TEXT_LENGTH = 64;
