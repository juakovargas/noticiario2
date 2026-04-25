import FlashMessage from '@/Components/FlashMessage';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import {
    Clapperboard,
    FileText,
    LayoutGrid,
    Menu,
    Newspaper,
    NotebookPen,
    Radio,
    X,
} from 'lucide-react';
import { PropsWithChildren, useMemo, useState } from 'react';
import { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    routeName: string;
    icon: JSX.Element;
}

export default function EditorLayout({ children }: PropsWithChildren): JSX.Element {
    const [open, setOpen] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;

    const navItems = useMemo<NavItem[]>(
        () => [
            {
                label: 'Dashboard',
                href: route('editor.dashboard'),
                routeName: 'editor.dashboard',
                icon: <LayoutGrid className="h-4 w-4" />,
            },
            {
                label: 'Editions',
                href: route('editor.editions'),
                routeName: 'editor.editions',
                icon: <NotebookPen className="h-4 w-4" />,
            },
            {
                label: 'News Items',
                href: route('editor.news-items'),
                routeName: 'editor.news-items',
                icon: <Newspaper className="h-4 w-4" />,
            },
            {
                label: 'Scripts',
                href: route('editor.scripts'),
                routeName: 'editor.scripts',
                icon: <FileText className="h-4 w-4" />,
            },
            {
                label: 'Sources',
                href: route('editor.sources'),
                routeName: 'editor.sources',
                icon: <Radio className="h-4 w-4" />,
            },
            {
                label: 'Media',
                href: route('editor.media'),
                routeName: 'editor.media',
                icon: <Clapperboard className="h-4 w-4" />,
            },
        ],
        [],
    );

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
                            Noticiario Editor
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
                                            ? 'bg-cyan-900 text-white shadow-md shadow-cyan-700/20'
                                            : 'text-slate-600 hover:bg-cyan-50 hover:text-cyan-900'
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
                    <header className="sticky top-0 z-20 border-b border-slate-200/70 bg-white/75 backdrop-blur">
                        <div className="flex h-16 items-center justify-between px-4 md:px-8">
                            <button
                                className="inline-flex items-center justify-center rounded-md border border-slate-300 p-2 md:hidden"
                                onClick={() => setOpen(true)}
                            >
                                <Menu className="h-4 w-4" />
                            </button>

                            <div className="text-sm">
                                <p className="font-semibold text-slate-900">{user?.name}</p>
                                <p className="text-slate-500">Editor panel</p>
                            </div>

                            <Button asChild variant="outline" size="sm">
                                <Link href={route('logout')} method="post" as="button">
                                    Logout
                                </Link>
                            </Button>
                        </div>
                    </header>

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
