import { Card, CardContent } from '@/Components/ui/card';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head } from '@inertiajs/react';

export default function PublishedContent(): JSX.Element {
    return (
        <ViewerLayout title="Published Content">
            <Head title="Published Content" />

            <Card>
                <CardContent className="pt-6">
                    <p className="text-sm text-slate-700">
                        Future published content and read-only viewer data will appear in this panel.
                    </p>
                </CardContent>
            </Card>
        </ViewerLayout>
    );
}
