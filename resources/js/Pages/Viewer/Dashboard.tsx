import PanelPlaceholder from '@/Components/PanelPlaceholder';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard(): JSX.Element {
    return (
        <ViewerLayout title="Viewer Dashboard">
            <Head title="Viewer Dashboard" />

            <div className="mb-6">
                <p className="text-sm text-slate-600">
                    Read-only dashboard for reviewing what has already been published.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <PanelPlaceholder
                    title="Published Content"
                    description="Browse published content in a read-only mode for monitoring and validation."
                    badgeLabel="Read-only"
                />

                <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-base font-semibold text-slate-900">Quick access</h2>
                    <p className="mt-1 text-sm text-slate-600">Open the published content section.</p>
                    <Link
                        href={route('viewer.published-content.index')}
                        className="mt-3 inline-block text-sm font-medium text-emerald-700 hover:text-emerald-900"
                    >
                        Go to Published Content
                    </Link>
                </div>
            </div>
        </ViewerLayout>
    );
}
