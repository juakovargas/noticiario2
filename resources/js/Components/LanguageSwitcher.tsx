import { router, usePage } from '@inertiajs/react';
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
            <select
                id="language"
                className="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 shadow-sm focus:border-slate-500 focus:outline-none md:text-sm"
                value={props.i18n.locale}
                onChange={(event) => {
                    router.post(
                        route('locale.update'),
                        { locale: event.target.value },
                        { preserveScroll: true, preserveState: true },
                    );
                }}
            >
                {props.i18n.availableLocales.map((locale) => (
                    <option key={locale.code} value={locale.code}>
                        {locale.code === 'en' ? t('English') : t('Spanish')}
                    </option>
                ))}
            </select>
        </div>
    );
}
