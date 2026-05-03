import { useTranslations } from '@/i18n/useTranslations';
import { Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';

interface TopbarMessagesButtonProps {
    canOpenMessages: boolean;
    unreadMessagesCount?: number;
}

export default function TopbarMessagesButton({ canOpenMessages, unreadMessagesCount = 0 }: TopbarMessagesButtonProps): JSX.Element | null {
    const { t } = useTranslations();

    if (!canOpenMessages) {
        return null;
    }

    const badge = unreadMessagesCount > 0 ? (unreadMessagesCount > 99 ? '99+' : unreadMessagesCount) : null;

    return (
        <Link
            href={route('messages.index')}
            aria-label={t('topbar.messages')}
            title={t('topbar.messages')}
            className="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white"
        >
            <Mail className="h-5 w-5" />
            {badge && (
                <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-900">
                    {badge}
                </span>
            )}
        </Link>
    );
}
