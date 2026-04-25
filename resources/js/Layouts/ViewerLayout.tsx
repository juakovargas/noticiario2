import FlashMessage from '@/Components/FlashMessage';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import { Eye, LayoutGrid } from 'lucide-react';
import { PropsWithChildren } from 'react';
import { PageProps } from '@/types';

interface ViewerLayoutProps extends PropsWithChildren {
    title?: string;
}

function hasPermission(permissions: string[] | undefined, permission: string): boolean {
    return !!permissions?.includes(permission);
}

export default function ViewerLayout({ children, title }: ViewerLayoutProps): JSX.Element {
    const page = usePage<PageProps>();
    const user = page.props.auth.user;

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,_#dcfce7,_transparent_45%),linear-gradient(135deg,_#f8fafc_30%,_#e2e8f0_100%)] text-slate-800">
            <div className="mx-auto flex min-h-screen max-w-7xl">
                <aside className="hidden w-64 border-r border-slate-200/70 bg-white/80 px-5 py-6 backdrop-blur md:block">
                    <div className="mb-8">
                        <p className="text-lg font-bold tracking-tight text-emerald-900">Noticiario Viewer</p>
                        <p className="text-xs text-slate-500">Read-only panel</p>
                    </div>

                    <nav className="space-y-1.5">
                        <Link
                            href={route('viewer.dashboard')}
                            className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.dashboard')
                                    ? 'bg-emerald-900 text-white'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900'
                            }`}
                        >
                            <LayoutGrid className="h-4 w-4" />
                            Dashboard
                        </Link>

                        <Link
                            href={route('viewer.published-content.index')}
                            className={`flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.published-content.*')
                                    ? 'bg-emerald-900 text-white'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900'
                            }`}
                        >
                            <Eye className="h-4 w-4" />
                            Published Content
                        </Link>
                    </nav>
                </aside>

                <div className="flex w-full flex-1 flex-col">
                    <header className="sticky top-0 z-20 border-b border-slate-200/70 bg-white/80 backdrop-blur">
                        <div className="flex h-16 items-center justify-between px-4 md:px-8">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
                                <p className="text-xs text-slate-500">Read-only access</p>
                            </div>

                            <div className="flex items-center gap-2">
                                {hasPermission(user?.permissions, 'editor.access') && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('editor.dashboard')}>Go to editor panel</Link>
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
                        {title && <h1 className="mb-4 text-2xl font-bold text-slate-900">{title}</h1>}
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
