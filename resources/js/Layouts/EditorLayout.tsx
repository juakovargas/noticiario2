import FlashMessage from '@/Components/FlashMessage';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
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
            title: t('Workspace'),
            items: [
                { label: t('Dashboard'), routeName: 'editor.dashboard' },
                { label: t('Editorial Desk'), routeName: 'editor.editorial-desk.index' },
                { label: t('Editorial Requests'), routeName: 'editor.editorial-requests.index' },
            ],
        },
        {
            title: t('News'),
            items: [
                { label: t('News Items'), routeName: 'editor.news-items.index' },
                { label: t('News Sources'), routeName: 'editor.news-sources.index' },
                { label: t('News Categories'), routeName: 'editor.news-categories.index' },
                { label: t('Locations'), routeName: 'editor.locations.index' },
            ],
        },
        {
            title: t('Editions'),
            items: [
                { label: t('Editions'), routeName: 'editor.editions.index' },
                { label: t('Scripts'), routeName: 'editor.scripts.index' },
                { label: t('Editorial Schedules'), routeName: 'editor.editorial-schedules.index' },
                { label: t('Editorial Runs'), routeName: 'editor.editorial-schedule-runs.index' },
            ],
        },
        {
            title: t('Production'),
            items: [
                { label: t('Audio'), routeName: 'editor.audio' },
                { label: t('Video'), routeName: 'editor.video' },
                { label: t('Media Renders'), routeName: 'editor.media-renders' },
            ],
        },
        {
            title: t('Publishing'),
            items: [
                { label: t('Publications'), routeName: 'editor.publications' },
                { label: t('Social Channels'), routeName: 'editor.social-channels' },
            ],
        },
        {
            title: t('Settings'),
            items: [
                { label: t('Editorial Templates'), routeName: 'editor.editorial-templates.index' },
                { label: t('AI Prompt Templates'), routeName: 'editor.ai-prompt-templates.index' },
            ],
        },
    ];

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_#ecfeff,_transparent_55%),linear-gradient(145deg,_#f8fafc_30%,_#dbeafe_100%)] text-slate-800">
            <div className="mx-auto flex min-h-screen max-w-7xl">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/80 bg-white/90 px-5 py-6 shadow-xl backdrop-blur transition-transform md:static md:translate-x-0 md:shadow-none ${
                        open ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('editor.dashboard')} className="text-lg font-extrabold tracking-tight text-cyan-900">
                            {t('Noticiario')} {t('Editor Panel')}
                        </Link>
                        <button onClick={() => setOpen(false)} className="md:hidden">
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                    <nav className="space-y-5">
                        {sections.map((section) => (
                            <div key={section.title}>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{section.title}</p>
                                <div className="space-y-1">
                                    {section.items.map((item) => {
                                        const active =
                                            route().current(item.routeName) ||
                                            route().current(item.routeName.replace('.index', '.*'));
                                        return (
                                            <Link
                                                key={item.routeName}
                                                href={route(item.routeName)}
                                                className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
                                                    active
                                                        ? 'bg-cyan-900 text-white'
                                                        : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900'
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
                <div className="flex w-full flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-slate-200/70 bg-white/75 backdrop-blur">
                        <div className="flex h-16 items-center justify-between gap-3 px-4 md:px-8">
                            <button
                                className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 md:hidden"
                                onClick={() => setOpen(true)}
                            >
                                <Menu className="h-4 w-4" />
                            </button>
                            <div className="text-sm">
                                <p className="font-semibold text-slate-900">{user?.name}</p>
                                <p className="text-slate-500">{t('Editor Panel')}</p>
                            </div>
                            <div className="ml-auto flex items-center gap-2">
                                <LanguageSwitcher />
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
