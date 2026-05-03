import UserAvatar from '@/Components/UserAvatar';
import { useTranslations } from '@/i18n/useTranslations';
import { PageProps, User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, LogOut, UserRound } from 'lucide-react';
import { useRef, useState } from 'react';
import { dropdownItemClass, dropdownPanelClass, useCloseOnOutside } from './dropdown';

interface TopbarUserMenuProps {
    user?: User | null;
    panel: 'admin' | 'editor' | 'viewer';
}

export default function TopbarUserMenu({ user, panel }: TopbarUserMenuProps): JSX.Element {
    const { t } = useTranslations();
    const page = usePage<PageProps>();
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const canEditProfile = route().has?.('profile.edit') ?? true;

    useCloseOnOutside(menuRef, open, () => setOpen(false));

    return (
        <div ref={menuRef} className="relative">
            <button
                type="button"
                aria-label={t('topbar.userMenu')}
                title={t('topbar.userMenu')}
                className="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 text-left text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-700 dark:hover:bg-slate-800"
                onClick={() => setOpen((value) => !value)}
            >
                <UserAvatar user={user} size="sm" />
                <span className="hidden min-w-0 sm:block">
                    <span className="block max-w-32 truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{user?.name}</span>
                    <span className="block max-w-32 truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {page.props.i18n.locale.toUpperCase()}
                    </span>
                </span>
                <ChevronDown className="hidden h-4 w-4 text-slate-400 sm:block" />
            </button>

            {open && (
                <div className={dropdownPanelClass}>
                    <div className="mb-2 flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-800/70">
                        <UserAvatar user={user} size="md" />
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-950 dark:text-white">{user?.name}</p>
                            <p className="truncate text-xs text-slate-500 dark:text-slate-400">{user?.email}</p>
                        </div>
                    </div>

                    {canEditProfile && (
                        <Link
                            href={route('profile.edit', { panel })}
                            className={dropdownItemClass}
                            onClick={() => setOpen(false)}
                        >
                            <UserRound className="h-4 w-4" />
                            <span>{t('topbar.editProfile')}</span>
                        </Link>
                    )}

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className={dropdownItemClass}
                        onClick={() => setOpen(false)}
                    >
                        <LogOut className="h-4 w-4" />
                        <span>{t('topbar.signOut')}</span>
                    </Link>
                </div>
            )}
        </div>
    );
}
