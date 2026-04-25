import FlashMessage from '@/Components/FlashMessage';
import { Button } from '@/Components/ui/button';
import { Link, usePage } from '@inertiajs/react';
import { Eye, LayoutGrid } from 'lucide-react';
import { PropsWithChildren } from 'react';
import { PageProps } from '@/types';

interface ViewerLayoutProps extends PropsWithChildren {
    title?: string;
}

export default function ViewerLayout({ children, title }: ViewerLayoutProps): JSX.Element {
    const page = usePage<PageProps>();

    return (
        <div className="min-h-screen bg-[radial-gradient(circle_at_top_right,_#dcfce7,_transparent_45%),linear-gradient(135deg,_#f8fafc_30%,_#e2e8f0_100%)] text-slate-800">
            <header className="border-b border-slate-200/70 bg-white/80 backdrop-blur">
                <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 md:px-8">
                    <div>
                        <p className="text-lg font-bold tracking-tight text-emerald-900">Noticiario Viewer</p>
                        <p className="text-xs text-slate-500">Read-only area</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={route('viewer.dashboard')}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.dashboard')
                                    ? 'bg-emerald-900 text-white'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900'
                            }`}
                        >
                            <span className="inline-flex items-center gap-1">
                                <LayoutGrid className="h-4 w-4" />
                                Dashboard
                            </span>
                        </Link>

                        <Link
                            href={route('viewer.published-content')}
                            className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                route().current('viewer.published-content')
                                    ? 'bg-emerald-900 text-white'
                                    : 'text-slate-600 hover:bg-emerald-50 hover:text-emerald-900'
                            }`}
                        >
                            <span className="inline-flex items-center gap-1">
                                <Eye className="h-4 w-4" />
                                Published Content
                            </span>
                        </Link>
                    </div>

                    <div className="flex items-center gap-3">
                        <p className="hidden text-sm text-slate-600 md:block">{page.props.auth.user?.name}</p>
                        <Button asChild variant="outline" size="sm">
                            <Link href={route('logout')} method="post" as="button">
                                Logout
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-4 py-6 md:px-8">
                {title && <h1 className="mb-4 text-2xl font-bold text-slate-900">{title}</h1>}
                <div className="mb-4">
                    <FlashMessage />
                </div>
                {children}
            </main>
        </div>
    );
}
