import { useTranslations } from '@/i18n/useTranslations';
import { Appearance, applyAppearance, getInitialAppearance } from '@/lib/theme';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, MonitorCog, Moon, Sun } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { dropdownItemClass, dropdownPanelClass, useCloseOnOutside } from './dropdown';
import TopbarIconButton from './TopbarIconButton';

const icons: Record<Appearance, JSX.Element> = {
    light: <Sun className="h-5 w-5" />,
    dark: <Moon className="h-5 w-5" />,
    system: <MonitorCog className="h-5 w-5" />,
};

export default function TopbarAppearanceToggle(): JSX.Element {
    const { t } = useTranslations();
    const page = usePage<PageProps>();
    const userAppearance = page.props.auth.user?.appearance;
    const isAuthenticated = Boolean(page.props.auth.user);
    const [appearance, setAppearance] = useState<Appearance>(() => getInitialAppearance(userAppearance));
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);

    useCloseOnOutside(menuRef, open, () => setOpen(false));

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

    const options = useMemo<Array<{ value: Appearance; label: string; icon: JSX.Element }>>(
        () => [
            { value: 'light', label: t('topbar.lightMode'), icon: <Sun className="h-4 w-4" /> },
            { value: 'dark', label: t('topbar.darkMode'), icon: <Moon className="h-4 w-4" /> },
            { value: 'system', label: t('topbar.systemMode'), icon: <MonitorCog className="h-4 w-4" /> },
        ],
        [t],
    );

    const onSelect = (value: Appearance): void => {
        setAppearance(value);
        setOpen(false);
        applyAppearance(value);

        if (isAuthenticated) {
            router.patch(route('preferences.appearance.update'), { appearance: value }, { preserveScroll: true });
        }
    };

    return (
        <div ref={menuRef} className="relative">
            <TopbarIconButton
                icon={icons[appearance]}
                label={t('topbar.toggleTheme')}
                active={open}
                onClick={() => setOpen((value) => !value)}
            />

            {open && (
                <div className={dropdownPanelClass}>
                    <p className="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        {t('topbar.toggleTheme')}
                    </p>
                    {options.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            className={cn(dropdownItemClass, appearance === option.value && 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-white')}
                            onClick={() => onSelect(option.value)}
                        >
                            {option.icon}
                            <span className="flex-1">{option.label}</span>
                            {appearance === option.value && <Check className="h-4 w-4 text-cyan-600 dark:text-cyan-300" />}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
