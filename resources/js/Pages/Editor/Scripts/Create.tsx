import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option { id: number; title: string; default_language_code?: string | null; }
interface Language { id:number; code:string; name:string; native_name:string|null; flag_emoji:string|null; }
interface Props { editions: Option[]; statuses: string[]; selectedEditionId?: string; languages: Language[]; }

export default function Create({ editions, statuses, selectedEditionId, languages }: Props): JSX.Element {
    const { data, setData, post, processing } = useForm({ edition_id: selectedEditionId ?? '', title: '', status: 'draft', language: '', intro: '', body: '', outro: '', estimated_duration_seconds: '' });
    const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); post(route('editor.scripts.store')); };
    return <EditorLayout><Head title="Create Script" /><AdminPageHeader title="Create Script" description="Create an editorial script manually." /><Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4"><div><Label>Title</Label><Input value={data.title} onChange={(e)=>setData('title', e.target.value)} /></div><div><Label>Edition</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.edition_id} onChange={(e)=>{ const v=e.target.value; setData('edition_id', v); if (!data.language) { const ed=editions.find((item)=>String(item.id)===v); if (ed?.default_language_code) setData('language', ed.default_language_code); } }}><option value="">Select edition</option>{editions.map((v)=><option key={v.id} value={v.id}>{v.title}</option>)}</select></div><div className="grid gap-4 md:grid-cols-3"><div><Label>Status</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.status} onChange={(e)=>setData('status', e.target.value)}>{statuses.map((v)=><option key={v} value={v}>{v}</option>)}</select></div><div><Label>Editorial language</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.language} onChange={(e)=>setData('language', e.target.value)}><option value="">Auto</option>{languages.map((v)=><option key={v.id} value={v.code}>{v.flag_emoji} {v.native_name || v.name} ({v.code})</option>)}</select></div><div><Label>Estimated duration</Label><Input type="number" value={data.estimated_duration_seconds} onChange={(e)=>setData('estimated_duration_seconds', e.target.value)} /></div></div><div><Label>Intro</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.intro} onChange={(e)=>setData('intro', e.target.value)} /></div><div><Label>Body</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={8} value={data.body} onChange={(e)=>setData('body', e.target.value)} /></div><div><Label>Outro</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.outro} onChange={(e)=>setData('outro', e.target.value)} /></div><div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.scripts.index')}>Cancel</Link></Button></div></form></CardContent></Card></EditorLayout>;
}
