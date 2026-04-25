import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editions" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Editions</h1>
                <p className="text-sm text-slate-600">Create planned noticiario editions such as morning, afternoon, night or special editions.</p>
            </div>

            <PanelPlaceholder title="Editions" description="Create planned noticiario editions such as morning, afternoon, night or special editions." />
        </EditorLayout>
    );
}
