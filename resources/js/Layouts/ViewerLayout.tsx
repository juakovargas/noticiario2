import FlashMessage from '@/Components/FlashMessage';
import PageContainer from '@/Components/Layout/PageContainer';
import { AppTopbar } from '@/Components/Topbar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Eye, LayoutGrid, Map, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';
import { PageProps } from '@/types';

interface ViewerLayoutProps extends PropsWithChildren {
    title?: string;
}

interface NavItem {
    label: string;
    href: string;
    routeName: string;
    icon: JSX.Element;
}

export default function ViewerLayout({ children, title }: ViewerLayoutProps): JSX.Element {
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const impersonation = page.props.impersonation;
    const { t } = useTranslations();
    const canOpenMessages = route().has?.('messages.index') ?? false;
    const unreadMessagesCount = page.props.messages?.unread_count ?? 0;
    const navItems: NavItem[] = [
        { label: t('Dashboard'), href: route('viewer.dashboard'), routeName: 'viewer.dashboard', icon: <LayoutGrid className="h-4 w-4" /> },
        { label: t('World Map'), href: route('viewer.world-map.index'), routeName: 'viewer.world-map.*', icon: <Map className="h-4 w-4" /> },
        { label: t('Published Content'), href: route('viewer.published-content'), routeName: 'viewer.published-content', icon: <Eye className="h-4 w-4" /> },
    ];

    return (
        <div className="min-h-screen w-full bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
            <div className="flex min-h-screen w-full">
                <aside
                    className={cn(
                        'fixed inset-y-0 left-0 z-40 w-72 transform overflow-y-auto border-r border-slate-200/80 bg-white/95 px-5 py-6 shadow-xl backdrop-blur transition-all duration-300 dark:border-slate-800 dark:bg-slate-900/95 md:sticky md:top-0 md:translate-x-0 md:shadow-none',
                        mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full',
                        sidebarCollapsed ? 'md:w-20 md:px-3' : 'md:w-72 md:px-5',
                    )}
                >
                    <div className={cn('mb-8 flex items-center justify-between gap-3', sidebarCollapsed && 'md:justify-center')}>
                        <Link
                            href={route('viewer.dashboard')}
                            className="flex min-w-0 items-center gap-3 text-lg font-extrabold tracking-tight text-emerald-800 dark:text-emerald-300"
                            title={`${t('Noticiario')} ${t('Viewer Panel')}`}
                        >
                            <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-800 text-sm font-black text-white shadow-sm dark:bg-emerald-300 dark:text-slate-950">
                                N
                            </span>
                            <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>
                                {t('Noticiario')} {t('Viewer Panel')}
                            </span>
                        </Link>
                        <button
                            onClick={() => setMobileSidebarOpen(false)}
                            className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800 md:hidden"
                            type="button"
                            aria-label={t('topbar.closeMenu')}
                            title={t('topbar.closeMenu')}
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <nav className="space-y-2">
                        {navItems.map((item) => {
                            const active = route().current(item.routeName);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    title={item.label}
                                    className={cn(
                                        'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                        active
                                            ? 'bg-emerald-800 text-white dark:bg-emerald-400 dark:text-slate-900'
                                            : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-emerald-300',
                                        sidebarCollapsed && 'md:justify-center md:px-2',
                                    )}
                                >
                                    {item.icon}
                                    <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>{item.label}</span>
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <AppTopbar
                        panel="viewer"
                        title={`${t('Noticiario')} ${t('Viewer Panel')}`}
                        subtitle={t('topbar.viewerSubtitle')}
                        user={user}
                        sidebarCollapsed={sidebarCollapsed}
                        onSidebarToggle={() => setSidebarCollapsed((value) => !value)}
                        onMobileMenuOpen={() => setMobileSidebarOpen(true)}
                        canOpenMessages={canOpenMessages}
                        unreadMessagesCount={unreadMessagesCount}
                    />

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

                    <main className="min-w-0 flex-1">
                        <PageContainer>
                            {title && <h1 className="mb-4 text-2xl font-bold text-slate-900 dark:text-slate-100">{title}</h1>}
                            <div className="mb-4">
                                <FlashMessage />
                            </div>
                            {children}
                        </PageContainer>
                    </main>
                </div>
            </div>
        </div>
    );
}
