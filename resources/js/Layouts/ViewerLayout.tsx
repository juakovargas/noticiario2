import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import PageContainer from '@/Components/Layout/PageContainer';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { Link, usePage } from '@inertiajs/react';
import { Eye, LayoutGrid, Map } from 'lucide-react';
import { PropsWithChildren } from 'react';
import { PageProps } from '@/types';

interface ViewerLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function ViewerLayout({ children, title }: ViewerLayoutProps): JSX.Element {
    const page = usePage<PageProps>();
    const impersonation = page.props.impersonation;
    const { t } = useTranslations();

    return (
        <div className="min-h-screen bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
            <header className="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/85">
                <div className="flex min-h-16 w-full flex-wrap items-center justify-between gap-3 px-4 py-3 md:px-8 lg:px-10">
                    <div>
                        <p className="text-lg font-bold tracking-tight text-emerald-800 dark:text-emerald-300">
                            {t('Noticiario')} {t('Viewer Panel')}
                        </p>
                        <p className="text-xs text-slate-500 dark:text-slate-400">{t('Read-only area')}</p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href={route('viewer.dashboard')}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.dashboard')
                                    ? 'bg-emerald-800 text-white dark:bg-emerald-400 dark:text-slate-900'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-emerald-300'
                            }`}
                        >
                            <span className="inline-flex items-center gap-1">
                                <LayoutGrid className="h-4 w-4" />
                                {t('Dashboard')}
                            </span>
                        </Link>

                        <Link
                            href={route('viewer.world-map.index')}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.world-map.*')
                                    ? 'bg-emerald-800 text-white dark:bg-emerald-400 dark:text-slate-900'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-emerald-300'
                            }`}
                        >
                            <span className="inline-flex items-center gap-1">
                                <Map className="h-4 w-4" />
                                {t('World Map')}
                            </span>
                        </Link>

                        <Link
                            href={route('viewer.published-content')}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.published-content')
                                    ? 'bg-emerald-800 text-white dark:bg-emerald-400 dark:text-slate-900'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-emerald-300'
                            }`}
                        >
                            <span className="inline-flex items-center gap-1">
                                <Eye className="h-4 w-4" />
                                {t('Published Content')}
                            </span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-2 md:gap-3">
                        <LanguageSwitcher />
                        <ThemeSwitcher />
                        <div className="hidden items-center gap-2 md:flex">
                            <UserAvatar user={page.props.auth.user} size="sm" />
                            <p className="text-sm text-slate-600 dark:text-slate-300">{page.props.auth.user?.name}</p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('profile.edit', { panel: 'viewer' })}>{t('Preferences')}</Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('logout')} method="post" as="button">
                                {t('Logout')}
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            {impersonation.active && (
                <div className="border-b border-amber-300/80 bg-amber-100 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100">
                    <div className="flex items-center justify-between gap-3 px-4 py-2 md:px-8 lg:px-10">
                        <p className="font-medium">
                            {t('You are impersonating')} {impersonation.current_user_name}
                        </p>
                        <Button asChild size="sm" variant="secondary">
                            <Link href={route('admin.impersonation.stop')} method="post" as="button">
                                {t('Return to admin')}
                            </Link>
                        </Button>
                    </div>
                </div>
            )}

            <main className="min-w-0">
                <PageContainer>
                    {title && <h1 className="mb-4 text-2xl font-bold text-slate-900 dark:text-slate-100">{title}</h1>}
                    <div className="mb-4">
                        <FlashMessage />
                    </div>
                    {children}
                </PageContainer>
            </main>
        </div>
    );
}
