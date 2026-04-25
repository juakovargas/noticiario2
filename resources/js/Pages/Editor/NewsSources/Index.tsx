import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="News Sources" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">News Sources</h1>
                <p className="text-sm text-slate-600">Manage RSS feeds, websites, manual sources and future API-based news sources.</p>
            </div>

            <PanelPlaceholder title="News Sources" description="Manage RSS feeds, websites, manual sources and future API-based news sources." />
        </EditorLayout>
    );
}
