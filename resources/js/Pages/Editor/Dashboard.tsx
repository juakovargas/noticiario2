import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

interface Props { stats: { editions:number; newsItems:number; scripts:number; sources:number; }; }
interface ModuleCard { title: string; href: string; stat?: keyof Props['stats']; }

const modules: ModuleCard[] = [
    { title: 'News Items', href: 'editor.news-items.index', stat: 'newsItems' },
    { title: 'News Sources', href: 'editor.news-sources.index', stat: 'sources' },
    { title: 'News Categories', href: 'editor.news-categories.index' },
    { title: 'Locations', href: 'editor.locations.index' },
    { title: 'Editions', href: 'editor.editions.index', stat: 'editions' },
    { title: 'Scripts', href: 'editor.scripts.index', stat: 'scripts' },
];

export default function Dashboard({ stats }: Props): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editor Dashboard" />
            <AdminPageHeader title="Editor Dashboard" description="Main editorial workspace foundation for content operations." />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {modules.map((module) => (
                    <Link key={module.title} href={route(module.href)}>
                        <Card className="h-full transition hover:-translate-y-0.5 hover:shadow-md">
                            <CardHeader>
                                <CardTitle>{module.title}</CardTitle>
                                <CardDescription>Open module</CardDescription>
                            </CardHeader>
                            <CardContent className="text-xs text-slate-500">
                                {module.stat ? `Records: ${stats[module.stat]}` : 'Module ready'}
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </EditorLayout>
    );
}
