import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';
interface Edition { title:string; edition_type:string; location:string|null; scheduled_for:string|null; language:string|null; status:string; target_duration_seconds:number|null; description:string|null; }
interface Props { edition:Edition; newsItems:Array<{id:number; title:string}>; }
export default function Show({ edition, newsItems }: Props): JSX.Element {
 return <EditorLayout><Head title={edition.title} /><AdminPageHeader title={edition.title} description="Edition detail." /><Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Type:</strong> {edition.edition_type}</p><p><strong>Location:</strong> {edition.location || '-'}</p><p><strong>Scheduled for:</strong> {edition.scheduled_for || '-'}</p><p><strong>Language:</strong> {edition.language || '-'}</p><p><strong>Status:</strong> {edition.status}</p><p><strong>Target duration:</strong> {edition.target_duration_seconds || '-'} seconds</p><p><strong>Description:</strong> {edition.description || '-'}</p><div><strong>Selected news items:</strong>{newsItems.length ? <ul className="list-disc pl-6">{newsItems.map((item)=><li key={item.id}>{item.title}</li>)}</ul> : <p className="text-slate-500">No selected news items yet.</p>}</div><Button asChild variant="secondary"><Link href={route('editor.editions.index')}>Back</Link></Button></CardContent></Card></EditorLayout>;
}
