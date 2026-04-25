import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

const modules = [
    {
        title: 'Editions',
        description: 'Plan and manage edition cycles.',
        href: 'editor.editions',
    },
    {
        title: 'News Items',
        description: 'Editorial news workflow will be added here.',
        href: 'editor.news-items',
    },
    {
        title: 'Scripts',
        description: 'Script generation and review pipeline entry point.',
        href: 'editor.scripts',
    },
    {
        title: 'Sources',
        description: 'Source curation and validation will live in this section.',
        href: 'editor.sources',
    },
    {
        title: 'Media',
        description: 'Media assets and future rendering operations.',
        href: 'editor.media',
    },
];

export default function Dashboard(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editor Dashboard" />

            <AdminPageHeader
                title="Editor Dashboard"
                description="Main editorial workspace foundation for upcoming modules."
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {modules.map((module) => (
                    <Link key={module.title} href={route(module.href)}>
                        <Card className="h-full transition hover:-translate-y-0.5 hover:shadow-md">
                            <CardHeader>
                                <CardTitle>{module.title}</CardTitle>
                                <CardDescription>{module.description}</CardDescription>
                            </CardHeader>
                            <CardContent className="text-xs text-slate-500">
                                Open placeholder
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </EditorLayout>
    );
}
