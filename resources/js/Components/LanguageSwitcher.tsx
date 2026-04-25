import { router, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { PageProps } from '@/types';
import { useTranslations } from '@/i18n/useTranslations';

export default function LanguageSwitcher(): JSX.Element {
    const { props } = usePage<PageProps>();
    const { t } = useTranslations();

    return (
        <div className="flex items-center gap-2">
            <label htmlFor="language" className="text-xs font-medium text-slate-500 md:text-sm">
                {t('Language')}
            </label>
            <div className="relative">
                <select
                    id="language"
                    className="appearance-none rounded-md border border-slate-300 bg-white py-1 pl-2 pr-8 text-xs text-slate-700 shadow-sm focus:border-slate-500 focus:outline-none md:text-sm"
                    value={props.i18n.locale}
                    onChange={(event) => {
                        router.post(route('locale.update'), { locale: event.target.value }, { preserveScroll: true, preserveState: true });
                    }}
                >
                    {props.i18n.availableLocales.map((locale) => (
                        <option key={locale.code} value={locale.code}>
                            {locale.flag_emoji ? `${locale.flag_emoji} ` : ''}{locale.native_name || locale.name || locale.code}
                        </option>
                    ))}
                </select>
                <ChevronDown className="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-500" />
            </div>
        </div>
    );
}
