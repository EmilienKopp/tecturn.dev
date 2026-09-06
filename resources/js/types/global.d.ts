import type { Auth } from '@/types/auth';
import type { Team } from '@/types/teams';
import type { LintPolicy } from '@/types/generated';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(
            pattern: string,
            options?: { eager?: boolean },
        ) => Record<string, T>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            lintPolicy: LintPolicy;
            [key: string]: unknown;
        };
    }
}

// CSS-only export of @animotion/core; no type declarations shipped.
declare module '@animotion/core/theme';
