import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import PageContainer from '@/Components/Layout/PageContainer';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { Link, usePage } from '@inertiajs/react';
import { Bot, ChartNoAxesCombined, Globe, Image, LayoutGrid, LockKeyhole, Map, Menu, ShieldCheck, Users, X } from 'lucide-react';
import { PropsWithChildren, useMemo, useState } from 'react';
import { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    routeName: string;
    icon: JSX.Element;
}

export default function AdminLayout({ children }: PropsWithChildren): JSX.Element {
    const [open, setOpen] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const impersonation = page.props.impersonation;
    const { t } = useTranslations();

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
        ],
        [t],
    );

    return (
        <div className="min-h-screen w-full bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
            <div className="flex min-h-screen w-full">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/80 bg-white/95 px-5 py-6 shadow-xl backdrop-blur transition-transform dark:border-slate-800 dark:bg-slate-900/95 md:sticky md:top-0 md:translate-x-0 md:shadow-none ${
                        open ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('admin.dashboard')} className="text-lg font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
                            {t('Noticiario')} {t('Admin Panel')}
                        </Link>
                        <button onClick={() => setOpen(false)} className="text-slate-600 dark:text-slate-200 md:hidden">
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
                                    className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition ${
                                        active
                                            ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100'
                                    }`}
                                >
                                    {item.icon}
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col bg-transparent">
                    <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/85">
                        <div className="flex h-16 items-center gap-3 px-4 md:px-8 lg:px-10">
                            <button
                                className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 text-slate-700 dark:border-slate-700 dark:text-slate-200 md:hidden"
                                onClick={() => setOpen(true)}
                            >
                                <Menu className="h-4 w-4" />
                            </button>

                            <div className="flex min-w-0 items-center gap-2 text-sm">
                                <UserAvatar user={user} size="sm" />
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-slate-900 dark:text-slate-100">{user?.name}</p>
                                    <p className="truncate text-slate-500 dark:text-slate-400">{user?.email}</p>
                                </div>
                            </div>

                            <div className="ml-auto flex items-center gap-2">
                                <LanguageSwitcher />
                                <ThemeSwitcher />
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('profile.edit', { panel: 'admin' })}>{t('Preferences')}</Link>
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
