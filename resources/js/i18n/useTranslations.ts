import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { LocaleCode, translations } from './translations';

export function useTranslations(): { t: (key: string) => string; locale: LocaleCode } {
    const { props } = usePage<PageProps>();
    const locale = (props.i18n?.locale ?? 'en') as LocaleCode;

    const t = (key: string): string => {
        return translations[locale]?.[key] ?? key;
    };

    return { t, locale };
}
