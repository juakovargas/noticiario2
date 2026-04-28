export type Appearance = 'light' | 'dark' | 'system';

export function resolveAppearance(appearance: Appearance): 'light' | 'dark' {
    if (appearance !== 'system') {
        return appearance;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function applyAppearance(appearance: Appearance): void {
    const resolved = resolveAppearance(appearance);
    document.documentElement.classList.toggle('dark', resolved === 'dark');
    localStorage.setItem('appearance', appearance);
}

export function getInitialAppearance(userAppearance?: string | null): Appearance {
    if (userAppearance && ['light', 'dark', 'system'].includes(userAppearance)) {
        return userAppearance as Appearance;
    }

    const cached = localStorage.getItem('appearance');
    if (cached && ['light', 'dark', 'system'].includes(cached)) {
        return cached as Appearance;
    }

    return 'system';
}
