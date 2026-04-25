import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

const sectionCards = [
    {
        title: 'News Desk',
        description: 'News collection and organization workflow.',
        links: [
            { label: 'News Items', routeName: 'editor.news-items.index' },
            { label: 'News Sources', routeName: 'editor.news-sources.index' },
            { label: 'News Categories', routeName: 'editor.news-categories.index' },
            { label: 'Locations', routeName: 'editor.locations.index' },
        ],
    },
    {
        title: 'Edition Workflow',
        description: 'Plan and produce noticiario editions and scripts.',
        links: [
            { label: 'Editions', routeName: 'editor.editions.index' },
            { label: 'Scripts', routeName: 'editor.scripts.index' },
        ],
    },
    {
        title: 'Production',
        description: 'Future generation pipelines for audio and video.',
        links: [
            { label: 'Audio', routeName: 'editor.audio.index' },
            { label: 'Video', routeName: 'editor.video.index' },
            { label: 'Media Renders', routeName: 'editor.media-renders.index' },
        ],
    },
    {
        title: 'Publishing',
        description: 'Prepare publications and social distribution workflows.',
        links: [
            { label: 'Publications', routeName: 'editor.publications.index' },
            { label: 'Social Channels', routeName: 'editor.social-channels.index' },
        ],
    },
    {
        title: 'Settings',
        description: 'Editorial and AI template configuration placeholders.',
        links: [
            { label: 'Editorial Templates', routeName: 'editor.editorial-templates.index' },
            { label: 'AI Prompt Templates', routeName: 'editor.ai-prompt-templates.index' },
        ],
    },
];

export default function Dashboard(): JSX.Element {
    return (
        <EditorLayout>
            <Head title="Editor Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900">Editor Dashboard</h1>
                <p className="text-sm text-slate-600">
                    Main workspace for the Noticiario editorial team. Use the sections below to navigate the planned workflow.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {sectionCards.map((section) => (
                    <Card key={section.title}>
                        <CardHeader>
                            <CardTitle>{section.title}</CardTitle>
                            <CardDescription>{section.description}</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            {section.links.map((link) => (
                                <Link
                                    key={link.routeName}
                                    href={route(link.routeName)}
                                    className="block text-sm font-medium text-cyan-700 hover:text-cyan-900"
                                >
                                    {link.label}
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </EditorLayout>
    );
}
