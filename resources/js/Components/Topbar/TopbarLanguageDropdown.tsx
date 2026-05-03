import { useTranslations } from '@/i18n/useTranslations';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Check, Languages } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { dropdownItemClass, dropdownPanelClass, useCloseOnOutside } from './dropdown';
import TopbarIconButton from './TopbarIconButton';

export default function TopbarLanguageDropdown(): JSX.Element {
    const { props } = usePage<PageProps>();
    const { t } = useTranslations();
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);

    useCloseOnOutside(menuRef, open, () => setOpen(false));

    const currentLocale = useMemo(
        () => props.i18n.availableLocales.find((locale) => locale.code === props.i18n.locale) ?? props.i18n.availableLocales[0],
        [props.i18n.availableLocales, props.i18n.locale],
    );

    const updateLocale = (locale: string): void => {
        setOpen(false);
        router.post(
            route('locale.update'),
            { locale },
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    };

    return (
        <div ref={menuRef} className="relative">
            <TopbarIconButton
                icon={
                    <span className="inline-flex items-center gap-1">
                        <Languages className="h-5 w-5" />
                        <span className="text-[10px] font-bold uppercase leading-none">{props.i18n.locale}</span>
                    </span>
                }
                label={t('topbar.language')}
                active={open}
                onClick={() => setOpen((value) => !value)}
            />

            {open && (
                <div className={dropdownPanelClass}>
                    <p className="px-3 pb-2 pt-1 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                        {t('topbar.language')}
                    </p>
                    {props.i18n.availableLocales.map((locale) => {
                        const selected = locale.code === currentLocale?.code;

                        return (
                            <button
                                key={locale.code}
                                type="button"
                                className={cn(dropdownItemClass, selected && 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-white')}
                                onClick={() => updateLocale(locale.code)}
                            >
                                <span className="w-6 text-base leading-none">{locale.flag_emoji || locale.code.toUpperCase()}</span>
                                <span className="flex-1">
                                    {locale.native_name || locale.name || locale.code.toUpperCase()}
                                </span>
                                {selected && <Check className="h-4 w-4 text-cyan-600 dark:text-cyan-300" />}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
