/**
 * Quiz-buzzer sounds for the presenter screen, synthesized with WebAudio so
 * the app ships no audio assets. The phone remote triggers these over the
 * control channel; they play on the presenting device, where a prior user
 * gesture has unlocked audio (phones block programmatic playback anyway).
 */

export type BuzzerSound = 'buzz' | 'ding';

let ctx: AudioContext | null = null;

/**
 * Creating (or resuming) the context inside a user-gesture handler unlocks
 * playback for the rest of the session. Call once from any click/keydown.
 */
export function unlockBuzzerAudio(): void {
    ctx ??= new AudioContext();

    if (ctx.state === 'suspended') {
        void ctx.resume();
    }
}

function tone(
    audio: AudioContext,
    options: {
        type: OscillatorType;
        frequency: number;
        startAt: number;
        duration: number;
        peak: number;
    },
): void {
    const oscillator = audio.createOscillator();
    const gain = audio.createGain();

    oscillator.type = options.type;
    oscillator.frequency.setValueAtTime(options.frequency, options.startAt);

    gain.gain.setValueAtTime(0.0001, options.startAt);
    gain.gain.exponentialRampToValueAtTime(
        options.peak,
        options.startAt + 0.015,
    );
    gain.gain.exponentialRampToValueAtTime(
        0.0001,
        options.startAt + options.duration,
    );

    oscillator.connect(gain).connect(audio.destination);
    oscillator.start(options.startAt);
    oscillator.stop(options.startAt + options.duration + 0.05);
}

export function playBuzzer(sound: BuzzerSound): void {
    unlockBuzzerAudio();

    if (!ctx || ctx.state !== 'running') {
        return;
    }

    const now = ctx.currentTime;

    if (sound === 'buzz') {
        // The classic wrong-answer "bzzzt": two detuned sawtooths beating
        // against each other in the low register.
        tone(ctx, {
            type: 'sawtooth',
            frequency: 110,
            startAt: now,
            duration: 0.7,
            peak: 0.25,
        });
        tone(ctx, {
            type: 'sawtooth',
            frequency: 116,
            startAt: now,
            duration: 0.7,
            peak: 0.25,
        });

        return;
    }

    // Correct-answer chime: two ascending bell tones.
    tone(ctx, {
        type: 'sine',
        frequency: 660,
        startAt: now,
        duration: 0.5,
        peak: 0.3,
    });
    tone(ctx, {
        type: 'sine',
        frequency: 880,
        startAt: now + 0.13,
        duration: 0.9,
        peak: 0.3,
    });
}
