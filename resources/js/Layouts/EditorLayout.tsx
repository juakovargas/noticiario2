import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import PageContainer from '@/Components/Layout/PageContainer';
import ThemeSwitcher from '@/Components/ThemeSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Mail, Menu, X } from 'lucide-react';
import { useState } from 'react';
import type { PropsWithChildren } from 'react';

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
    const canOpenMessages = route().has?.('messages.index') ?? false;
    const unreadMessagesCount = page.props.messages?.unread_count ?? 0;

    const sections: NavSection[] = [
        {
            title: t('editorNav.primary'),
            items: [
                { label: t('editorNav.dashboard'), routeName: 'editor.dashboard' },
                { label: t('editorNav.informativos'), routeName: 'editor.bulletin-types.index' },
                { label: t('editorNav.automation'), routeName: 'editor.automation.index' },
                { label: t('editorNav.executions'), routeName: 'editor.editorial-schedule-runs.index' },
                { label: t('editorNav.scripts'), routeName: 'editor.scripts.index' },
                { label: t('editorNav.sources'), routeName: 'editor.source-references.index' },
            ],
        },
        {
            title: t('editorNav.production'),
            items: [
                { label: t('editorNav.productionPrep'), routeName: 'editor.scripts.index' },
                { label: t('editorNav.audioFuture'), routeName: 'editor.audio' },
                { label: t('editorNav.videoFuture'), routeName: 'editor.video' },
                { label: t('editorNav.publishingFuture'), routeName: 'editor.publications' },
            ],
        },
        {
            title: t('editorNav.editorialConfig'),
            items: [
                { label: t('editorNav.schedules'), routeName: 'editor.editorial-schedules.index' },
                { label: t('editorNav.categories'), routeName: 'editor.news-categories.index' },
                { label: t('editorNav.locations'), routeName: 'editor.locations.index' },
                { label: t('editorNav.editorialTemplates'), routeName: 'editor.editorial-templates.index' },
            ],
        },
        {
            title: t('editorNav.secondaryDebug'),
            items: [
                { label: t('editorNav.workbench'), routeName: 'editor.workbench' },
                { label: t('editorNav.promptRuns'), routeName: 'editor.bulletin-prompt-runs.index' },
                { label: t('editorNav.promptTemplates'), routeName: 'editor.ai-prompt-templates.index' },
                { label: t('editorNav.editorialDesk'), routeName: 'editor.editorial-desk.index' },
                { label: t('editorNav.editions'), routeName: 'editor.editions.index' },
                { label: t('editorNav.newsItems'), routeName: 'editor.news-items.index' },
                { label: t('editorNav.newsSources'), routeName: 'editor.news-sources.index' },
            ],
        },
    ];

    if (canOpenMessages) {
        sections.push({ title: t('editorNav.messages'), items: [{ label: t('editorNav.messages'), routeName: 'messages.index' }] });
    }

    return (
        <div className="min-h-screen w-full bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-100">
            <div className="flex min-h-screen w-full">
                <aside className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/80 bg-white/95 px-5 py-6 shadow-xl backdrop-blur transition-transform dark:border-slate-800 dark:bg-slate-900/95 md:sticky md:top-0 md:translate-x-0 md:shadow-none ${open ? 'translate-x-0' : '-translate-x-full'}`}>
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('editor.dashboard')} className="text-lg font-extrabold tracking-tight text-cyan-900 dark:text-cyan-300">
                            {t('editorNav.brand')}
                        </Link>
                        <button onClick={() => setOpen(false)} className="text-slate-600 dark:text-slate-200 md:hidden" type="button">
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
                                                className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${active ? 'bg-cyan-800 text-white dark:bg-cyan-400 dark:text-slate-900' : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-cyan-300'}`}
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
                            <button className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 text-slate-700 dark:border-slate-700 dark:text-slate-200 md:hidden" onClick={() => setOpen(true)} type="button">
                                <Menu className="h-4 w-4" />
                            </button>
                            <div className="flex min-w-0 items-center gap-2 text-sm">
                                <UserAvatar user={user} size="sm" />
                                <div className="min-w-0">
                                    <p className="truncate font-semibold text-slate-900 dark:text-slate-100">{user?.name}</p>
                                    <p className="truncate text-slate-500 dark:text-slate-400">{t('editorNav.panel')}</p>
                                </div>
                            </div>
                            <div className="ml-auto flex items-center gap-2">
                                <LanguageSwitcher />
                                <ThemeSwitcher />
                                {canOpenMessages && (
                                    <Button asChild variant="outline" size="sm" className="gap-2">
                                        <Link href={route('messages.index')}>
                                            <Mail className="h-4 w-4" />
                                            {t('editorNav.messages')}{unreadMessagesCount > 0 && ` (${unreadMessagesCount > 99 ? '99+' : unreadMessagesCount})`}
                                        </Link>
                                    </Button>
                                )}
                                <Button asChild variant="outline" size="sm"><Link href={route('profile.edit', { panel: 'editor' })}>{t('common.preferences')}</Link></Button>
                                <Button asChild variant="outline" size="sm"><Link href={route('logout')} method="post" as="button">{t('common.logout')}</Link></Button>
                            </div>
                        </div>
                    </header>

                    {impersonation.active && (
                        <div className="border-b border-amber-300/80 bg-amber-100 text-sm text-amber-950 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100">
                            <div className="flex items-center justify-between gap-3 px-4 py-2 md:px-8 lg:px-10">
                                <p className="font-medium">{t('common.impersonating')} {impersonation.current_user_name}</p>
                                <Button asChild size="sm" variant="secondary">
                                    <Link href={route('admin.impersonation.stop')} method="post" as="button">{t('common.returnToAdmin')}</Link>
                                </Button>
                            </div>
                        </div>
                    )}

                    <main className="min-w-0 flex-1">
                        <PageContainer>
                            <div className="mb-4"><FlashMessage /></div>
                            {children}
                        </PageContainer>
                    </main>
                </div>
            </div>
        </div>
    );
}
