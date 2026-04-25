import FlashMessage from '@/Components/FlashMessage';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import {
    AudioLines,
    FileCode2,
    FileText,
    Globe,
    LayoutGrid,
    MapPin,
    Menu,
    Newspaper,
    Radio,
    Settings,
    Shapes,
    Share2,
    Video,
    WandSparkles,
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

interface NavGroup {
    title: string;
    items: NavItem[];
}

function hasPermission(permissions: string[] | undefined, permission: string): boolean {
    return !!permissions?.includes(permission);
}

export default function EditorLayout({ children }: PropsWithChildren): JSX.Element {
    const [open, setOpen] = useState(false);
    const page = usePage<PageProps>();
    const user = page.props.auth.user;

    const navGroups = useMemo<NavGroup[]>(
        () => [
            {
                title: 'Workspace',
                items: [
                    {
                        label: 'Dashboard',
                        href: route('editor.dashboard'),
                        routeName: 'editor.dashboard',
                        icon: <LayoutGrid className="h-4 w-4" />,
                    },
                ],
            },
            {
                title: 'News',
                items: [
                    {
                        label: 'News Items',
                        href: route('editor.news-items.index'),
                        routeName: 'editor.news-items.*',
                        icon: <Newspaper className="h-4 w-4" />,
                    },
                    {
                        label: 'News Sources',
                        href: route('editor.news-sources.index'),
                        routeName: 'editor.news-sources.*',
                        icon: <Radio className="h-4 w-4" />,
                    },
                    {
                        label: 'News Categories',
                        href: route('editor.news-categories.index'),
                        routeName: 'editor.news-categories.*',
                        icon: <Shapes className="h-4 w-4" />,
                    },
                    {
                        label: 'Locations',
                        href: route('editor.locations.index'),
                        routeName: 'editor.locations.*',
                        icon: <MapPin className="h-4 w-4" />,
                    },
                ],
            },
            {
                title: 'Editions',
                items: [
                    {
                        label: 'Editions',
                        href: route('editor.editions.index'),
                        routeName: 'editor.editions.*',
                        icon: <FileText className="h-4 w-4" />,
                    },
                    {
                        label: 'Scripts',
                        href: route('editor.scripts.index'),
                        routeName: 'editor.scripts.*',
                        icon: <FileCode2 className="h-4 w-4" />,
                    },
                ],
            },
            {
                title: 'Production',
                items: [
                    {
                        label: 'Audio',
                        href: route('editor.audio.index'),
                        routeName: 'editor.audio.*',
                        icon: <AudioLines className="h-4 w-4" />,
                    },
                    {
                        label: 'Video',
                        href: route('editor.video.index'),
                        routeName: 'editor.video.*',
                        icon: <Video className="h-4 w-4" />,
                    },
                    {
                        label: 'Media Renders',
                        href: route('editor.media-renders.index'),
                        routeName: 'editor.media-renders.*',
                        icon: <WandSparkles className="h-4 w-4" />,
                    },
                ],
            },
            {
                title: 'Publishing',
                items: [
                    {
                        label: 'Publications',
                        href: route('editor.publications.index'),
                        routeName: 'editor.publications.*',
                        icon: <Globe className="h-4 w-4" />,
                    },
                    {
                        label: 'Social Channels',
                        href: route('editor.social-channels.index'),
                        routeName: 'editor.social-channels.*',
                        icon: <Share2 className="h-4 w-4" />,
                    },
                ],
            },
            {
                title: 'Settings',
                items: [
                    {
                        label: 'Editorial Templates',
                        href: route('editor.editorial-templates.index'),
                        routeName: 'editor.editorial-templates.*',
                        icon: <Settings className="h-4 w-4" />,
                    },
                    {
                        label: 'AI Prompt Templates',
                        href: route('editor.ai-prompt-templates.index'),
                        routeName: 'editor.ai-prompt-templates.*',
                        icon: <WandSparkles className="h-4 w-4" />,
                    },
                ],
            },
        ],
        [],
    );

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_#ecfeff,_transparent_55%),linear-gradient(145deg,_#f8fafc_30%,_#dbeafe_100%)] text-slate-800">
            <div className="mx-auto flex min-h-screen max-w-7xl">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-80 transform overflow-y-auto border-r border-slate-200/80 bg-white/90 px-5 py-6 shadow-xl backdrop-blur transition-transform md:static md:translate-x-0 md:shadow-none ${
                        open ? 'translate-x-0' : '-translate-x-full'
                    }`}
                >
                    <div className="mb-8 flex items-center justify-between">
                        <div>
                            <Link href={route('editor.dashboard')} className="text-lg font-extrabold tracking-tight text-cyan-900">
                                Noticiario
                            </Link>
                            <p className="text-xs text-slate-500">Editorial workspace</p>
                        </div>
                        <button onClick={() => setOpen(false)} className="md:hidden">
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    <nav className="space-y-6">
                        {navGroups.map((group) => (
                            <div key={group.title}>
                                <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {group.title}
                                </p>
                                <div className="space-y-1.5">
                                    {group.items.map((item) => {
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
                                </div>
                            </div>
                        ))}
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
                                <p className="text-slate-500">{user?.email}</p>
                            </div>

                            <div className="flex items-center gap-2">
                                {hasPermission(user?.permissions, 'viewer.access') && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('viewer.dashboard')}>Viewer panel</Link>
                                    </Button>
                                )}

                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('logout')} method="post" as="button">
                                        Logout
                                    </Link>
                                </Button>
                            </div>
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
