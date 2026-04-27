import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { Link, usePage } from '@inertiajs/react';
import { Bot, ChartNoAxesCombined, Globe, LayoutGrid, LockKeyhole, Menu, ShieldCheck, Users, X } from 'lucide-react';
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
            {
                label: t('Dashboard'),
                href: route('admin.dashboard'),
                routeName: 'admin.dashboard',
                icon: <LayoutGrid className="h-4 w-4" />,
            },
            {
                label: t('Users'),
                href: route('admin.users.index'),
                routeName: 'admin.users.*',
                icon: <Users className="h-4 w-4" />,
            },
            {
                label: t('Roles'),
                href: route('admin.roles.index'),
                routeName: 'admin.roles.*',
                icon: <ShieldCheck className="h-4 w-4" />,
            },
            {
                label: t('Permissions'),
                href: route('admin.permissions.index'),
                routeName: 'admin.permissions.*',
                icon: <LockKeyhole className="h-4 w-4" />,
            },
            {
                label: t('Languages'),
                href: route('admin.languages.index'),
                routeName: 'admin.languages.*',
                icon: <Globe className="h-4 w-4" />,
            },
            {
                label: t('AI Providers'),
                href: route('admin.ai-providers.index'),
                routeName: 'admin.ai-providers.*',
                icon: <Bot className="h-4 w-4" />,
            },
            {
                label: t('SEO & Tracking'),
                href: route('admin.seo-settings.edit'),
                routeName: 'admin.seo-settings.*',
                icon: <ChartNoAxesCombined className="h-4 w-4" />,
            },
        ],
        [t],
    );

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,_#fef3c7,_transparent_50%),linear-gradient(140deg,_#f8fafc_20%,_#e2e8f0_100%)] text-slate-800">
            <div className="mx-auto flex min-h-screen max-w-7xl">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/70 bg-white/90 px-5 py-6 shadow-xl backdrop-blur transition-transform md:static md:translate-x-0 md:shadow-none ${
                        open ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('admin.dashboard')} className="text-lg font-extrabold tracking-tight text-slate-900">
                            {t('Noticiario')} {t('Admin Panel')}
                        </Link>
                        <button onClick={() => setOpen(false)} className="md:hidden">
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
                                            ? 'bg-slate-900 text-white shadow-md shadow-slate-500/20'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    }`}
                                >
                                    {item.icon}
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="flex w-full flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-slate-200/60 bg-white/70 backdrop-blur">
                        <div className="flex h-16 items-center justify-between gap-3 px-4 md:px-8">
                            <button
                                className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 md:hidden"
                                onClick={() => setOpen(true)}
                            >
                                <Menu className="h-4 w-4" />
                            </button>

                            <div className="flex items-center gap-2 text-sm">
                                <UserAvatar user={user} size="sm" />
                                <div>
                                    <p className="font-semibold text-slate-900">{user?.name}</p>
                                    <p className="text-slate-500">{user?.email}</p>
                                </div>
                            </div>

                            <div className="ml-auto flex items-center gap-2">
                                <LanguageSwitcher />
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
                        <div className="border-b border-amber-300 bg-amber-100 px-4 py-2 text-sm text-amber-900 md:px-8">
                            <div className="flex items-center justify-between gap-3">
                                <p className="font-medium">{t('You are impersonating')} {impersonation.current_user_name}</p>
                                <Button asChild size="sm" variant="secondary">
                                    <Link href={route('admin.impersonation.stop')} method="post" as="button">
                                        {t('Return to admin')}
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    )}

                    <main className="flex-1 px-4 py-6 md:px-8">
                        <div className="mb-4">
                            <FlashMessage />
                        </div>
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
