import { useTranslations } from '@/i18n/useTranslations';
import { Appearance, applyAppearance, getInitialAppearance } from '@/lib/theme';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function ThemeSwitcher(): JSX.Element {
    const { t } = useTranslations();
    const page = usePage<PageProps>();
    const userAppearance = page.props.auth.user?.appearance;
    const [appearance, setAppearance] = useState<Appearance>(() => getInitialAppearance(userAppearance));
    const isAuthenticated = Boolean(page.props.auth.user);

    useEffect(() => {
        const next = getInitialAppearance(userAppearance);
        setAppearance(next);
        applyAppearance(next);
    }, [userAppearance]);

    useEffect(() => {
        if (appearance !== 'system') {
            return;
        }

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const listener = () => applyAppearance('system');
        media.addEventListener('change', listener);
        return () => media.removeEventListener('change', listener);
    }, [appearance]);

    const options = useMemo<Array<{ value: Appearance; label: string }>>(
        () => [
            { value: 'light', label: t('Light mode') },
            { value: 'dark', label: t('Dark mode') },
            { value: 'system', label: t('System mode') },
        ],
        [t],
    );

    const onChange = (value: Appearance): void => {
        setAppearance(value);
        applyAppearance(value);

        if (!isAuthenticated) {
            return;
        }

        router.patch(route('preferences.appearance.update'), { appearance: value }, { preserveScroll: true });
    };

    return (
        <label className="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <span className="hidden md:inline">{t('Theme')}</span>
            <select
                value={appearance}
                onChange={(event) => onChange(event.target.value as Appearance)}
                className="rounded-md border-slate-300 py-1 text-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
