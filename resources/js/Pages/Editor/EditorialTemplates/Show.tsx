import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

interface Props {
    template: {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        edition_type: string | null;
        target_duration_seconds: number | null;
        intro_template: string | null;
        body_template: string | null;
        outro_template: string | null;
        is_active: boolean;
        sort_order: number;
        language?: { code: string; name: string; native_name: string | null; flag_emoji: string | null } | null;
        location?: { name: string } | null;
    };
}

export default function Show({ template }: Props): JSX.Element {
    return <EditorLayout><Head title={template.name} /><AdminPageHeader title={template.name} description="Editorial template details." />
        <Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Slug:</strong> {template.slug}</p><p><strong>Description:</strong> {template.description || '-'}</p><p><strong>Language:</strong> {template.language ? `${template.language.flag_emoji ?? ''} ${template.language.native_name || template.language.name} (${template.language.code})` : '-'}</p><p><strong>Location:</strong> {template.location?.name || '-'}</p><p><strong>Edition type:</strong> {template.edition_type || '-'}</p><p><strong>Target duration:</strong> {template.target_duration_seconds || '-'}</p><p><strong>Intro template:</strong> {template.intro_template || '-'}</p><p><strong>Body template:</strong> {template.body_template || '-'}</p><p><strong>Outro template:</strong> {template.outro_template || '-'}</p><p><strong>Active:</strong> {template.is_active ? 'Yes' : 'No'}</p><p><strong>Sort order:</strong> {template.sort_order}</p><Button asChild variant="secondary"><Link href={route('editor.editorial-templates.index')}>Back</Link></Button></CardContent></Card>
    </EditorLayout>;
}
