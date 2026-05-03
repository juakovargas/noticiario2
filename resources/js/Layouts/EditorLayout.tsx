import FlashMessage from '@/Components/FlashMessage';
import PageContainer from '@/Components/Layout/PageContainer';
import { AppTopbar } from '@/Components/Topbar';
import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarClock,
    Clapperboard,
    FileText,
    Gauge,
    Headphones,
    Inbox,
    Layers3,
    MapPin,
    Newspaper,
    RadioTower,
    Settings2,
    Share2,
    ShieldQuestion,
    SlidersHorizontal,
    Sparkles,
    TableProperties,
    Tags,
    X,
} from 'lucide-react';
import { useState } from 'react';
import type { PropsWithChildren } from 'react';

interface NavItem {
    label: string;
    routeName: string;
    icon: JSX.Element;
}

interface NavSection {
    title: string;
    items: NavItem[];
}

export default function EditorLayout({ children }: PropsWithChildren): JSX.Element {
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
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
                { label: t('editorNav.dashboard'), routeName: 'editor.dashboard', icon: <Gauge className="h-4 w-4" /> },
                { label: t('editorNav.informativos'), routeName: 'editor.bulletin-types.index', icon: <RadioTower className="h-4 w-4" /> },
                { label: t('editorNav.automation'), routeName: 'editor.automation.index', icon: <CalendarClock className="h-4 w-4" /> },
                { label: t('editorNav.executions'), routeName: 'editor.editorial-schedule-runs.index', icon: <TableProperties className="h-4 w-4" /> },
                { label: t('editorNav.scripts'), routeName: 'editor.scripts.index', icon: <FileText className="h-4 w-4" /> },
                { label: t('editorNav.sources'), routeName: 'editor.source-references.index', icon: <BadgeCheck className="h-4 w-4" /> },
            ],
        },
        {
            title: t('editorNav.production'),
            items: [
                { label: t('editorNav.productionPrep'), routeName: 'editor.scripts.index', icon: <Layers3 className="h-4 w-4" /> },
                { label: t('editorNav.audioFuture'), routeName: 'editor.audio', icon: <Headphones className="h-4 w-4" /> },
                { label: t('editorNav.videoFuture'), routeName: 'editor.video', icon: <Clapperboard className="h-4 w-4" /> },
                { label: t('editorNav.publishingFuture'), routeName: 'editor.publications', icon: <Share2 className="h-4 w-4" /> },
            ],
        },
        {
            title: t('editorNav.editorialConfig'),
            items: [
                { label: t('editorNav.schedules'), routeName: 'editor.editorial-schedules.index', icon: <CalendarClock className="h-4 w-4" /> },
                { label: t('editorNav.categories'), routeName: 'editor.news-categories.index', icon: <Tags className="h-4 w-4" /> },
                { label: t('editorNav.locations'), routeName: 'editor.locations.index', icon: <MapPin className="h-4 w-4" /> },
                { label: t('editorNav.editorialTemplates'), routeName: 'editor.editorial-templates.index', icon: <SlidersHorizontal className="h-4 w-4" /> },
            ],
        },
        {
            title: t('editorNav.secondaryDebug'),
            items: [
                { label: t('editorNav.workbench'), routeName: 'editor.workbench', icon: <Sparkles className="h-4 w-4" /> },
                { label: t('editorNav.promptRuns'), routeName: 'editor.bulletin-prompt-runs.index', icon: <ShieldQuestion className="h-4 w-4" /> },
                { label: t('editorNav.promptTemplates'), routeName: 'editor.ai-prompt-templates.index', icon: <SlidersHorizontal className="h-4 w-4" /> },
                { label: t('editorNav.editorialDesk'), routeName: 'editor.editorial-desk.index', icon: <Inbox className="h-4 w-4" /> },
                { label: t('editorNav.editions'), routeName: 'editor.editions.index', icon: <Newspaper className="h-4 w-4" /> },
                { label: t('editorNav.newsItems'), routeName: 'editor.news-items.index', icon: <Newspaper className="h-4 w-4" /> },
                { label: t('editorNav.newsSources'), routeName: 'editor.news-sources.index', icon: <Settings2 className="h-4 w-4" /> },
            ],
        },
    ];

    if (canOpenMessages) {
        sections.push({ title: t('editorNav.messages'), items: [{ label: t('editorNav.messages'), routeName: 'messages.index', icon: <Inbox className="h-4 w-4" /> }] });
    }

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
                            href={route('editor.dashboard')}
                            className="flex min-w-0 items-center gap-3 text-lg font-extrabold tracking-tight text-cyan-900 dark:text-cyan-300"
                            title={t('editorNav.brand')}
                        >
                            <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-cyan-800 text-sm font-black text-white shadow-sm dark:bg-cyan-300 dark:text-slate-950">
                                N
                            </span>
                            <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>{t('editorNav.brand')}</span>
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

                    <nav className="space-y-5">
                        {sections.map((section) => (
                            <div key={section.title}>
                                <p className={cn('mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500', sidebarCollapsed && 'md:sr-only')}>
                                    {section.title}
                                </p>
                                <div className="space-y-1">
                                    {section.items.map((item) => {
                                        const active = route().current(item.routeName) || route().current(item.routeName.replace('.index', '.*'));

                                        return (
                                            <Link
                                                key={item.routeName}
                                                href={route(item.routeName)}
                                                title={item.label}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                                    active
                                                        ? 'bg-cyan-800 text-white dark:bg-cyan-400 dark:text-slate-900'
                                                        : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-cyan-300',
                                                    sidebarCollapsed && 'md:justify-center md:px-2',
                                                )}
                                            >
                                                {item.icon}
                                                <span className={cn('truncate', sidebarCollapsed && 'md:sr-only')}>{item.label}</span>
                                            </Link>
                                        );
                                    })}
                                </div>
                            </div>
                        ))}
                    </nav>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col">
                    <AppTopbar
                        panel="editor"
                        title={t('editorNav.brand')}
                        subtitle={t('topbar.editorSubtitle')}
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
