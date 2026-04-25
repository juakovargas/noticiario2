import PanelPlaceholder from '@/Components/PanelPlaceholder';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

export default function Index(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Social Channels" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Social Channels</h1>
                <p className="text-sm text-slate-600">Configure future YouTube, TikTok, Instagram, Facebook, X/Twitter and other social channels.</p>
            </div>

            <PanelPlaceholder title="Social Channels" description="Configure future YouTube, TikTok, Instagram, Facebook, X/Twitter and other social channels." />
        </EditorLayout>
    );
}
