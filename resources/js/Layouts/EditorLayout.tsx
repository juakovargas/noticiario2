import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import PageContainer from '@/Components/Layout/PageContainer';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';
import { PageProps } from '@/types';

interface NavItem {
    label: string;
    routeName: string;
}
interface NavSection {
    title: string;
    items: NavItem[];
}

export default function EditorLayout({ children }: PropsWithChildren): JSX.Element {
    const [open, setOpen] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const impersonation = page.props.impersonation;
    const { t } = useTranslations();

    const sections: NavSection[] = [
        {
            title: t('Workspace / Trabajo'),
            items: [
                { label: t('Dashboard'), routeName: 'editor.dashboard' },
                { label: t('Editorial Workbench'), routeName: 'editor.workbench' },
                { label: t('World Map'), routeName: 'editor.world-map.index' },
            ],
        },
        {
            title: t('Production Workflow / Flujo de producción'),
            items: [
                { label: t('Bulletin Types'), routeName: 'editor.bulletin-types.index' },
                { label: t('Prompt Runs'), routeName: 'editor.bulletin-prompt-runs.index' },
                { label: t('Scripts'), routeName: 'editor.scripts.index' },
                { label: t('Source verification'), routeName: 'editor.source-references.index' },
                { label: t('Editions'), routeName: 'editor.editions.index' },
            ],
        },
        {
            title: t('Content / Contenido'),
            items: [
                { label: t('News Items'), routeName: 'editor.news-items.index' },
                { label: t('News Sources'), routeName: 'editor.news-sources.index' },
                { label: t('News Categories'), routeName: 'editor.news-categories.index' },
                { label: t('Locations'), routeName: 'editor.locations.index' },
            ],
        },
        {
            title: t('Publishing preparation / Preparación de publicación'),
            items: [
                { label: t('Publications'), routeName: 'editor.publications' },
                { label: t('Social Channels'), routeName: 'editor.social-channels' },
                { label: t('Audio'), routeName: 'editor.audio' },
                { label: t('Video'), routeName: 'editor.video' },
            ],
        },
        {
            title: t('Configuration / Configuración'),
            items: [
                { label: t('Editorial Templates'), routeName: 'editor.editorial-templates.index' },
                { label: t('AI Prompt Templates'), routeName: 'editor.ai-prompt-templates.index' },
                { label: t('Editorial Desk'), routeName: 'editor.editorial-desk.index' },
                { label: t('Editorial Requests'), routeName: 'editor.editorial-requests.index' },
                { label: t('Editorial Schedules'), routeName: 'editor.editorial-schedules.index' },
                { label: t('Editorial Runs'), routeName: 'editor.editorial-schedule-runs.index' },
            ],
        },
    ];

    return (
        <div className="min-h-screen w-full bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
            <div className="flex min-h-screen w-full">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/80 bg-white/95 px-5 py-6 shadow-xl backdrop-blur transition-transform dark:border-slate-800 dark:bg-slate-900/95 md:sticky md:top-0 md:translate-x-0 md:shadow-none ${
                        open ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('editor.dashboard')} className="text-lg font-extrabold tracking-tight text-cyan-900 dark:text-cyan-300">
                            {t('Noticiario')} {t('Editor Panel')}
                        </Link>
                        <button onClick={() => setOpen(false)} className="text-slate-600 dark:text-slate-200 md:hidden">
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                    <nav className="space-y-5">
                        {sections.map((section) => (
                            <div key={section.title}>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{section.title}</p>
                                <div className="space-y-1">
                                    {section.items.map((item) => {
                                        const active = route().current(item.routeName) || route().current(item.routeName.replace('.index', '.*'));
                                        return (
                                            <Link
                                                key={item.routeName}
                                                href={route(item.routeName)}
                                                className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
                                                    active
                                                        ? 'bg-cyan-800 text-white dark:bg-cyan-400 dark:text-slate-900'
                                                        : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-cyan-300'
                                                }`}
                                            >
                                                {item.label}
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </nav>
                </aside>
                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/85">
                        <div className="flex h-16 items-center justify-between gap-3 px-4 md:px-8 lg:px-10">
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
                                    <p className="truncate text-slate-500 dark:text-slate-400">{t('Editor Panel')}</p>
                                </div>
                            </div>
                            <div className="ml-auto flex items-center gap-2">
                                <LanguageSwitcher />
                                <ThemeSwitcher />
                                <Button asChild variant="outline" size="sm"><Link href={route('messages.index')}>{t('Messages')} {((page.props as any).auth?.unreadMessagesCount ?? 0) > 0 ? `(${Math.min(99, (page.props as any).auth.unreadMessagesCount)}${((page.props as any).auth.unreadMessagesCount>99?'+' : '')})` : ''}</Link></Button>
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('profile.edit', { panel: 'editor' })}>{t('Preferences')}</Link>
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
