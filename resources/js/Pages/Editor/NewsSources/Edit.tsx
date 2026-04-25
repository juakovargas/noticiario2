import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
interface Source { id:number; name:string; slug:string; type:string; url:string|null; feed_url:string|null; description:string|null; language:string|null; country_code:string|null; is_active:boolean; trust_level:number; last_checked_at:string|null; }
interface Props { newsSource:Source; types:string[]; }
export default function Edit({ newsSource, types }: Props): JSX.Element {
 const { data, setData, put, processing } = useForm({ name:newsSource.name, slug:newsSource.slug, type:newsSource.type, url:newsSource.url || '', feed_url:newsSource.feed_url || '', description:newsSource.description || '', language:newsSource.language || '', country_code:newsSource.country_code || '', is_active:newsSource.is_active, trust_level:newsSource.trust_level, last_checked_at:newsSource.last_checked_at || '' });
 const submit=(e:any)=>{e.preventDefault();put(route('editor.news-sources.update', newsSource.id));};
 return <EditorLayout><Head title="Edit News Source" /><AdminPageHeader title="Edit News Source" description="Update source details." />
 <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4"><div><Label>Name</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
 <div><Label>Type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e)=>setData('type', e.target.value)}>{types.map((type)=><option key={type} value={type}>{type}</option>)}</select></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>URL</Label><Input value={data.url} onChange={(e)=>setData('url', e.target.value)} /></div><div><Label>Feed URL</Label><Input value={data.feed_url} onChange={(e)=>setData('feed_url', e.target.value)} /></div></div>
 <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e)=>setData('description', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-3"><div><Label>Language</Label><Input value={data.language} onChange={(e)=>setData('language', e.target.value)} /></div><div><Label>Country code</Label><Input value={data.country_code} onChange={(e)=>setData('country_code', e.target.value)} /></div><div><Label>Trust level</Label><Input type="number" min={1} max={5} value={data.trust_level} onChange={(e)=>setData('trust_level', Number(e.target.value))} /></div></div>
 <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />Active</label>
 <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.news-sources.index')}>Cancel</Link></Button></div></form></CardContent></Card></EditorLayout>;
}
