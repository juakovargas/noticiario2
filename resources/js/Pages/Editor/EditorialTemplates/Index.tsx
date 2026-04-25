import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editorial Templates" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Editorial Templates</h1>
                <p className="text-sm text-slate-600">Manage reusable editorial structures for different types of noticiario.</p>
            </div>

            <PanelPlaceholder title="Editorial Templates" description="Manage reusable editorial structures for different types of noticiario." />
        </EditorLayout>
    );
}
