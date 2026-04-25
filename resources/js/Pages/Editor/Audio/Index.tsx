import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Audio" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Audio</h1>
                <p className="text-sm text-slate-600">Prepare script-to-voice generation workflows.</p>
            </div>

            <PanelPlaceholder title="Audio" description="Prepare script-to-voice generation workflows." />
        </EditorLayout>
    );
}
