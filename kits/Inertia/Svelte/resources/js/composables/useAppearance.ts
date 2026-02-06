import type { Appearance, ResolvedAppearance } from '@/types';
import type { Readable, Writable } from 'svelte/store';
import { derived, get, writable } from 'svelte/store';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Writable<Appearance>;
    resolvedAppearance: Readable<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const appearance = writable<Appearance>('system');

const resolvedAppearance = derived(appearance, (value): ResolvedAppearance => {
    return isDarkMode(value) ? 'dark' : 'light';
});

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const isDarkMode = (value: Appearance): boolean => {
    return value === 'dark' || (value === 'system' && prefersDark());
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') return;
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (value: Appearance): void => {
    if (typeof document === 'undefined') return;
    const isDark = isDarkMode(value);
    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const mediaQuery = (): MediaQueryList | null => {
    if (typeof window === 'undefined') return null;
    return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = (): void => {
    applyTheme(get(appearance));
};

const getStoredAppearance = (): Appearance => {
    if (typeof window === 'undefined') return 'system';
    return (localStorage.getItem('appearance') as Appearance) || 'system';
};

export function initializeTheme(): void {
    if (typeof window === 'undefined') return;

    if (!localStorage.getItem('appearance')) {
        localStorage.setItem('appearance', 'system');
        setCookie('appearance', 'system');
    }

    const storedAppearance = getStoredAppearance();
    appearance.set(storedAppearance);
    applyTheme(storedAppearance);

    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function updateAppearance(value: Appearance): void {
    appearance.set(value);
    localStorage.setItem('appearance', value);
    setCookie('appearance', value);
    applyTheme(value);
}

export function useAppearance(): UseAppearanceReturn {
    return {
        appearance,
        resolvedAppearance,
        updateAppearance,
    };
}
