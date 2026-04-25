import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';
interface Script { title:string; edition:string|null; status:string; language:string|null; intro:string|null; body:string|null; outro:string|null; estimated_duration_seconds:number|null; approved_at:string|null; approved_by:string|null; }
interface Props { script:Script; }
export default function Show({ script }: Props): JSX.Element {
 return <EditorLayout><Head title={script.title} /><AdminPageHeader title={script.title} description="Script detail." /><Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Edition:</strong> {script.edition || '-'}</p><p><strong>Status:</strong> {script.status}</p><p><strong>Language:</strong> {script.language || '-'}</p><p><strong>Intro:</strong> {script.intro || '-'}</p><p><strong>Body:</strong> {script.body || '-'}</p><p><strong>Outro:</strong> {script.outro || '-'}</p><p><strong>Estimated duration:</strong> {script.estimated_duration_seconds || '-'} seconds</p><p><strong>Approved at:</strong> {script.approved_at || '-'}</p><p><strong>Approved by:</strong> {script.approved_by || '-'}</p><Button asChild variant="secondary"><Link href={route('editor.scripts.index')}>Back</Link></Button></CardContent></Card></EditorLayout>;
}
