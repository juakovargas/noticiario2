import FlashMessage from '@/Components/FlashMessage';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { PropsWithChildren, useState } from 'react';
import { PageProps } from '@/types';

interface NavItem { label: string; routeName: string; }
interface NavSection { title: string; items: NavItem[]; }

export default function EditorLayout({ children }: PropsWithChildren): JSX.Element {
    const [open, setOpen] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;

    const sections: NavSection[] = [
        { title: 'Workspace', items: [{ label: 'Dashboard', routeName: 'editor.dashboard' }] },
        { title: 'News', items: [
            { label: 'News Items', routeName: 'editor.news-items.index' },
            { label: 'News Sources', routeName: 'editor.news-sources.index' },
            { label: 'News Categories', routeName: 'editor.news-categories.index' },
            { label: 'Locations', routeName: 'editor.locations.index' },
        ]},
        { title: 'Editions', items: [
            { label: 'Editions', routeName: 'editor.editions.index' },
            { label: 'Scripts', routeName: 'editor.scripts.index' },
        ]},
        { title: 'Production', items: [
            { label: 'Audio', routeName: 'editor.audio' },
            { label: 'Video', routeName: 'editor.video' },
            { label: 'Media Renders', routeName: 'editor.media-renders' },
        ]},
        { title: 'Publishing', items: [
            { label: 'Publications', routeName: 'editor.publications' },
            { label: 'Social Channels', routeName: 'editor.social-channels' },
        ]},
        { title: 'Settings', items: [
            { label: 'Editorial Templates', routeName: 'editor.editorial-templates' },
            { label: 'AI Prompt Templates', routeName: 'editor.ai-prompt-templates' },
        ]},
    ];

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_#ecfeff,_transparent_55%),linear-gradient(145deg,_#f8fafc_30%,_#dbeafe_100%)] text-slate-800">
            <div className="mx-auto flex min-h-screen max-w-7xl">
                <aside className={`fixed inset-y-0 left-0 z-40 w-72 transform border-r border-slate-200/80 bg-white/90 px-5 py-6 shadow-xl backdrop-blur transition-transform md:static md:translate-x-0 md:shadow-none ${open ? 'translate-x-0' : '-translate-x-full'}`}>
                    <div className="mb-8 flex items-center justify-between">
                        <Link href={route('editor.dashboard')} className="text-lg font-extrabold tracking-tight text-cyan-900">Noticiario Editor</Link>
                        <button onClick={() => setOpen(false)} className="md:hidden"><X className="h-5 w-5" /></button>
                    </div>
                    <nav className="space-y-5">
                        {sections.map((section) => (
                            <div key={section.title}>
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{section.title}</p>
                                <div className="space-y-1">
                                    {section.items.map((item) => {
                                        const active = route().current(item.routeName) || route().current(item.routeName.replace('.index', '.*'));
                                        return <Link key={item.routeName} href={route(item.routeName)} className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${active ? 'bg-cyan-900 text-white' : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900'}`}>{item.label}</Link>;
                                    })}
                                </div>
                            </div>
                        ))}
                    </nav>
                </aside>
                <div className="flex w-full flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-slate-200/70 bg-white/75 backdrop-blur">
                        <div className="flex h-16 items-center justify-between px-4 md:px-8">
                            <button className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 md:hidden" onClick={() => setOpen(true)}><Menu className="h-4 w-4" /></button>
                            <div className="text-sm"><p className="font-semibold text-slate-900">{user?.name}</p><p className="text-slate-500">Editor panel</p></div>
                            <Button asChild variant="outline" size="sm"><Link href={route('logout')} method="post" as="button">Logout</Link></Button>
                        </div>
                    </header>
                    <main className="flex-1 px-4 py-6 md:px-8"><div className="mb-4"><FlashMessage /></div>{children}</main>
                </div>
            </div>
        </div>
    );
}
