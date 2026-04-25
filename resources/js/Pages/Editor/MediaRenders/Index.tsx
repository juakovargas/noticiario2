import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Media Renders" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Media Renders</h1>
                <p className="text-sm text-slate-600">Track generated audio/video renders and their statuses.</p>
            </div>

            <PanelPlaceholder title="Media Renders" description="Track generated audio/video renders and their statuses." />
        </EditorLayout>
    );
}
