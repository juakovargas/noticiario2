import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Publications" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Publications</h1>
                <p className="text-sm text-slate-600">Manage content publication to social platforms.</p>
            </div>

            <PanelPlaceholder title="Publications" description="Manage content publication to social platforms." />
        </EditorLayout>
    );
}
