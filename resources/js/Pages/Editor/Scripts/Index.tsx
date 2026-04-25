import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Scripts" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Scripts</h1>
                <p className="text-sm text-slate-600">Write, review and approve scripts generated from selected news items.</p>
            </div>

            <PanelPlaceholder title="Scripts" description="Write, review and approve scripts generated from selected news items." />
        </EditorLayout>
    );
}
