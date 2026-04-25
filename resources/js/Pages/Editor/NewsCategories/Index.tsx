import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="News Categories" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">News Categories</h1>
                <p className="text-sm text-slate-600">Manage thematic categories such as politics, sports, culture, science, technology, economy and esports.</p>
            </div>

            <PanelPlaceholder title="News Categories" description="Manage thematic categories such as politics, sports, culture, science, technology, economy and esports." />
        </EditorLayout>
    );
}
