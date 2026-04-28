import { useTranslations } from '@/i18n/useTranslations';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

export default function FlashMessage(): JSX.Element | null {
    const { flash } = usePage<PageProps>().props;
    const { t } = useTranslations();

    if (flash.success) {
        return (
            <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/60 dark:bg-emerald-900/30 dark:text-emerald-200">
                {t(flash.success)}
            </div>
        );
    }

    if (flash.error) {
        return (
            <div className="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700/60 dark:bg-rose-900/30 dark:text-rose-200">
                {t(flash.error)}
            </div>
        );
    }

    return null;
}
