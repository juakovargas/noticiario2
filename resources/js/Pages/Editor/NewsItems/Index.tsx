import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="News Items" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">News Items</h1>
                <p className="text-sm text-slate-600">Manage collected or manually created news items before they are selected for editions.</p>
            </div>

            <PanelPlaceholder title="News Items" description="Manage collected or manually created news items before they are selected for editions." />
        </EditorLayout>
    );
}
