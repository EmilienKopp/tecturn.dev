import { page } from '@inertiajs/svelte';

export type RegistrationMode = 'open' | 'invitation' | 'closed';

export type Features = {
    registration: RegistrationMode;
};

export type FeaturesState = {
    readonly all: Features;
    readonly registration: RegistrationMode;
    /** Anyone can sign in and create an account without an invitation. */
    readonly canSelfRegister: boolean;
    /** Visitors can request access to the private beta. */
    readonly canRequestBeta: boolean;
};

const DEFAULT_FEATURES: Features = {
    registration: 'closed',
};

/**
 * Reactive accessor for the global feature flags shared by the Inertia
 * middleware (see HandleInertiaRequests). Import and call inside a component:
 *
 *   const features = useFeatures();
 *   {#if features.canRequestBeta} ... {/if}
 */
export function useFeatures(): FeaturesState {
    const all = $derived(
        (page.props.features as Features | undefined) ?? DEFAULT_FEATURES,
    );

    return {
        get all() {
            return all;
        },
        get registration() {
            return all.registration;
        },
        get canSelfRegister() {
            return all.registration === 'open';
        },
        get canRequestBeta() {
            return all.registration === 'invitation';
        },
    };
}
