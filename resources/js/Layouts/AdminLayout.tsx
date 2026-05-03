import FlashMessage from '@/Components/FlashMessage';
import PageContainer from '@/Components/Layout/PageContainer';
import { AppTopbar } from '@/Components/Topbar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Bot, ChartNoAxesCombined, Globe, Image, LayoutGrid, LockKeyhole, Map, ShieldCheck, Users, X } from 'lucide-react';
import { PropsWithChildren, useMemo, useState } from 'react';
import { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    routeName: string;
    icon: JSX.Element;
}

export default function AdminLayout({ children }: PropsWithChildren): JSX.Element {
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const impersonation = page.props.impersonation;
    const { t } = useTranslations();
    const canOpenMessages = route().has?.('messages.index') ?? false;
    const unreadMessagesCount = page.props.messages?.unread_count ?? 0;

    const navItems = useMemo<NavItem[]>(
        () => [
            { label: t('Dashboard'), href: route('admin.dashboard'), routeName: 'admin.dashboard', icon: <LayoutGrid className="h-4 w-4" /> },
            { label: t('Users'), href: route('admin.users.index'), routeName: 'admin.users.*', icon: <Users className="h-4 w-4" /> },
            { label: t('Roles'), href: route('admin.roles.index'), routeName: 'admin.roles.*', icon: <ShieldCheck className="h-4 w-4" /> },
            { label: t('Permissions'), href: route('admin.permissions.index'), routeName: 'admin.permissions.*', icon: <LockKeyhole className="h-4 w-4" /> },
            { label: t('Languages'), href: route('admin.languages.index'), routeName: 'admin.languages.*', icon: <Globe className="h-4 w-4" /> },
            { label: t('AI Providers'), href: route('admin.ai-providers.index'), routeName: 'admin.ai-providers.*', icon: <Bot className="h-4 w-4" /> },
            { label: t('AI Request Logs'), href: route('admin.ai-request-logs.index'), routeName: 'admin.ai-request-logs.*', icon: <Bot className="h-4 w-4" /> },
            { label: t('Prompt Profiles'), href: route('admin.prompt-profiles.index'), routeName: 'admin.prompt-profiles.*', icon: <Bot className="h-4 w-4" /> },
            { label: t('Media Library'), href: route('admin.media-files.index'), routeName: 'admin.media-files.*', icon: <Image className="h-4 w-4" /> },
            { label: t('World Map'), href: route('admin.world-map.index'), routeName: 'admin.world-map.*', icon: <Map className="h-4 w-4" /> },
            { label: t('SEO & Tracking'), href: route('admin.seo-settings.edit'), routeName: 'admin.seo-settings.*', icon: <ChartNoAxesCombined className="h-4 w-4" /> },
            { label: t('Home Page'), href: route('admin.home-page-settings.index'), routeName: 'admin.home-page-settings.*', icon: <Globe className="h-4 w-4" /> },
            { label: t('Operations'), href: route('admin.operations.index'), routeName: 'admin.operations.*', icon: <ChartNoAxesCombined className="h-4 w-4" /> },
            { label: t('Operation Alert Settings'), href: route('admin.operation-alert-settings.index'), routeName: 'admin.operation-alert-settings.*', icon: <ChartNoAxesCombined className="h-4 w-4" /> },
        ],
        [t],
    );

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
                            href={route('admin.dashboard')}
                            className={cn('flex min-w-0 items-center gap-3 text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100')}
                            title={`${t('Noticiario')} ${t('Admin Panel')}`}
                        >
                            <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-sm font-black text-white shadow-sm dark:bg-white dark:text-slate-950">
                                N
                            </span>
                            <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>
                                {t('Noticiario')} {t('Admin Panel')}
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
                                    key={item.label}
                                    href={item.href}
                                    title={item.label}
                                    className={cn(`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition ${
                                        active
                                            ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                    }`, sidebarCollapsed && 'md:justify-center md:px-2')}
                                >
                                    {item.icon}
                                    <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>{item.label}</span>
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col bg-transparent">
                    <AppTopbar
                        panel="admin"
                        title={`${t('Noticiario')} ${t('Admin Panel')}`}
                        subtitle={t('topbar.adminSubtitle')}
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
