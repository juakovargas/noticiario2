import PanelPlaceholder from '@/Components/PanelPlaceholder';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <ViewerLayout title="Published Content">
            <Head title="Published Content" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Published Content</h1>
                <p className="text-sm text-slate-600">
                    Read-only area for reviewing content that has been published through editorial workflows.
                </p>
            </div>

            <PanelPlaceholder
                title="Published Content"
                description="This section will list published pieces and their metadata in read-only mode."
                badgeLabel="Read-only"
            />
        </ViewerLayout>
    );
}
