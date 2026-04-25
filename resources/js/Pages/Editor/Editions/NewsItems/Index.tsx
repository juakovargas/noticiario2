import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

interface Props {
    edition: { id: number; title: string };
    newsItems: Array<{
        id: number;
        title: string;
        source: string | null;
        category: string | null;
        location: string | null;
        sort_order: number;
        editorial_angle: string | null;
        included_in_script: boolean;
    }>;
}

export default function Index({ edition, newsItems }: Props): JSX.Element {
    return (
        <EditorLayout>
            <Head title={`${edition.title} - News Items`} />
            <AdminPageHeader title={edition.title} description="Selected edition news items." />
            <Card>
                <CardContent className="space-y-3 pt-6 text-sm">
                    {newsItems.length ? (
                        <ul className="list-disc pl-6">
                            {newsItems.map((item) => (
                                <li key={item.id}>
                                    #{item.sort_order} {item.title}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-slate-500">No selected news items yet.</p>
                    )}
                    <Link href={route('editor.editions.show', edition.id)} className="text-cyan-700 underline">
                        Back to edition
                    </Link>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
