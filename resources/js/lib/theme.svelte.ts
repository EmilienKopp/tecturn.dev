import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

/**
 * 'auto' = the On Air theme pair, following appearance (light/dark/system).
 * Any other value is a fixed daisyUI theme name.
 */
export type ThemeChoice = 'auto' | string;

export type ThemeOption = {
    name: string;
    label: string;
    scheme: ResolvedAppearance;
};

/* Keep in sync with the dark-theme list in HandleAppearance.php. */
const DARK_THEMES = new Set([
    'onair-dark',
    'dark',
    'synthwave',
    'halloween',
    'forest',
    'aqua',
    'black',
    'luxury',
    'dracula',
    'business',
    'night',
    'coffee',
    'dim',
    'sunset',
    'abyss',
]);

const BUILTIN_THEMES = [
    'light',
    'dark',
    'cupcake',
    'bumblebee',
    'emerald',
    'corporate',
    'synthwave',
    'retro',
    'cyberpunk',
    'valentine',
    'halloween',
    'garden',
    'forest',
    'aqua',
    'lofi',
    'pastel',
    'fantasy',
    'wireframe',
    'black',
    'luxury',
    'dracula',
    'cmyk',
    'autumn',
    'business',
    'acid',
    'lemonade',
    'night',
    'coffee',
    'winter',
    'dim',
    'nord',
    'sunset',
    'caramellatte',
    'abyss',
    'silk',
];

export const THEME_OPTIONS: ThemeOption[] = [
    { name: 'auto', label: 'On Air', scheme: 'light' },
    ...BUILTIN_THEMES.map((name) => ({
        name,
        label: name.charAt(0).toUpperCase() + name.slice(1),
        scheme: (DARK_THEMES.has(name)
            ? 'dark'
            : 'light') as ResolvedAppearance,
    })),
];

export type ThemeState = {
    appearance: {
        value: Appearance;
    };
    theme: {
        value: ThemeChoice;
    };
    resolvedAppearance: () => ResolvedAppearance;
    resolvedTheme: () => string;
    updateAppearance: (value: Appearance) => void;
    updateTheme: (value: ThemeChoice) => void;
};

const appearance = $state<{ value: Appearance }>({ value: 'system' });
const theme = $state<{ value: ThemeChoice }>({ value: 'auto' });

let themeChangeMediaQuery: MediaQueryList | null = null;

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const isDarkMode = (value: Appearance): boolean => {
    return value === 'dark' || (value === 'system' && prefersDark());
};

const getResolvedTheme = (): string => {
    if (theme.value === 'auto') {
        return isDarkMode(appearance.value) ? 'onair-dark' : 'onair';
    }

    return theme.value;
};

const getResolvedAppearance = (): ResolvedAppearance => {
    return DARK_THEMES.has(getResolvedTheme()) ? 'dark' : 'light';
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const resolvedTheme = getResolvedTheme();
    const isDark = DARK_THEMES.has(resolvedTheme);

    document.documentElement.dataset.theme = resolvedTheme;
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') {
        return 'system';
    }

    const stored = localStorage.getItem('appearance');

    return stored === 'light' || stored === 'dark' || stored === 'system'
        ? stored
        : 'system';
};

const getStoredTheme = (): ThemeChoice => {
    if (typeof window === 'undefined') {
        return 'auto';
    }

    const stored = localStorage.getItem('theme');

    return stored && THEME_OPTIONS.some((option) => option.name === stored)
        ? stored
        : 'auto';
};

const handleSystemThemeChange = (): void => {
    applyTheme();
};

const detachThemeChangeListener = (): void => {
    if (!themeChangeMediaQuery) {
        return;
    }

    themeChangeMediaQuery.removeEventListener(
        'change',
        handleSystemThemeChange,
    );
    themeChangeMediaQuery = null;
};

export function initializeTheme(): () => void {
    if (typeof window === 'undefined') {
        return () => {};
    }

    if (!localStorage.getItem('appearance')) {
        localStorage.setItem('appearance', 'system');
        setCookie('appearance', 'system');
    }

    if (!localStorage.getItem('theme')) {
        localStorage.setItem('theme', 'auto');
        setCookie('theme', 'auto');
    }

    appearance.value = getStoredAppearance();
    theme.value = getStoredTheme();
    applyTheme();

    detachThemeChangeListener();
    themeChangeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    themeChangeMediaQuery.addEventListener('change', handleSystemThemeChange);

    return detachThemeChangeListener;
}

export function updateAppearance(value: Appearance): void {
    appearance.value = value;

    if (typeof window !== 'undefined') {
        localStorage.setItem('appearance', value);
    }

    setCookie('appearance', value);
    applyTheme();
}

export function updateTheme(value: ThemeChoice): void {
    theme.value = value;

    if (typeof window !== 'undefined') {
        localStorage.setItem('theme', value);
    }

    setCookie('theme', value);
    applyTheme();
}

export function themeState(): ThemeState {
    return {
        appearance,
        theme,
        resolvedAppearance: getResolvedAppearance,
        resolvedTheme: getResolvedTheme,
        updateAppearance,
        updateTheme,
    };
}
