import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="AI Prompt Templates" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">AI Prompt Templates</h1>
                <p className="text-sm text-slate-600">Manage future AI prompts used for summaries, scripts and editorial transformations.</p>
            </div>

            <PanelPlaceholder title="AI Prompt Templates" description="Manage future AI prompts used for summaries, scripts and editorial transformations." />
        </EditorLayout>
    );
}
