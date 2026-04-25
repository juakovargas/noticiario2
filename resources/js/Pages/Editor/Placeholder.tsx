import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head } from '@inertiajs/react';

interface EditorPlaceholderProps {
    title: string;
    description: string;
}

export default function Placeholder({ title, description }: EditorPlaceholderProps): JSX.Element {
    return (
        <EditorLayout>
            <Head title={title} />

            <AdminPageHeader title={title} description="Editor module placeholder" />

            <Card>
                <CardContent className="pt-6">
                    <p className="text-sm text-slate-700">{description}</p>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
