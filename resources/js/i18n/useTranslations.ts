import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { translations } from './translations';

export function useTranslations(): { t: (key: string) => string; locale: string } {
    const { props } = usePage<PageProps>();
    const locale = props.i18n?.locale ?? 'en';

    const t = (key: string): string => {
        return translations[locale as keyof typeof translations]?.[key] ?? translations.en[key] ?? key;
    };

    return { t, locale };
}
