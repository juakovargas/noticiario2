import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Locations" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Locations</h1>
                <p className="text-sm text-slate-600">Manage the geographic scope of the news: global, countries, regions, cities or custom areas.</p>
            </div>

            <PanelPlaceholder title="Locations" description="Manage the geographic scope of the news: global, countries, regions, cities or custom areas." />
        </EditorLayout>
    );
}
