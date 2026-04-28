import { useTranslations } from '@/i18n/useTranslations';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';

export default function LanguageSwitcher(): JSX.Element {
    const { props } = usePage<PageProps>();
    const { t } = useTranslations();

    return (
        <div className="flex items-center gap-2">
            <label htmlFor="language" className="text-xs font-medium text-slate-500 dark:text-slate-400 md:text-sm">
                {t('Language')}
            </label>

            <div className="relative min-w-[120px]">
                <select
                    id="language"
                    className="block w-full rounded-md border border-slate-300 bg-white py-1 pl-2 pr-9 text-xs text-slate-700 shadow-sm outline-none focus:border-slate-500 focus:ring-1 focus:ring-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:focus:border-slate-500 dark:focus:ring-slate-500 md:text-sm [&::-ms-expand]:hidden"
                    style={{
                        appearance: 'none',
                        WebkitAppearance: 'none',
                        MozAppearance: 'none',
                        backgroundImage: 'none',
                    }}
                    value={props.i18n.locale}
                    onChange={(event) => {
                        router.post(
                            route('locale.update'),
                            { locale: event.target.value },
                            {
                                preserveScroll: true,
                                preserveState: true,
                            },
                        );
                    }}
                >
                    {props.i18n.availableLocales.map((locale) => (
                        <option key={locale.code} value={locale.code}>
                            {locale.flag_emoji ? `${locale.flag_emoji} ` : ''}
                            {locale.native_name || locale.name || locale.code}
                        </option>
                    ))}
                </select>

                <ChevronDown aria-hidden="true" className="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-500 dark:text-slate-400" />
            </div>
        </div>
    );
}
