import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard(): JSX.Element {
    return (
        <ViewerLayout title="Viewer Dashboard">
            <Head title="Viewer Dashboard" />

            <div className="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Published Content</CardTitle>
                        <CardDescription>Read-only content feed will be available here.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Link
                            href={route('viewer.published-content')}
                            className="text-sm font-medium text-emerald-700 hover:text-emerald-900"
                        >
                            Open placeholder page
                        </Link>
                    </CardContent>
                </Card>
            </div>
        </ViewerLayout>
    );
}
