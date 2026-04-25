import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Video" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Video</h1>
                <p className="text-sm text-slate-600">Prepare video generation workflows based on approved scripts.</p>
            </div>

            <PanelPlaceholder title="Video" description="Prepare video generation workflows based on approved scripts." />
        </EditorLayout>
    );
}
