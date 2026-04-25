import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
interface Props { types:string[]; }
export default function Create({ types }: Props): JSX.Element {
 const { data, setData, post, processing } = useForm({ name:'', slug:'', type:'manual', url:'', feed_url:'', description:'', language:'', country_code:'', is_active:true, trust_level:3, last_checked_at:'' });
 const submit=(e:any)=>{e.preventDefault();post(route('editor.news-sources.store'));};
 return <EditorLayout><Head title="Create News Source" /><AdminPageHeader title="Create News Source" description="Add a news source." />
 <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4"><div><Label>Name</Label><Input value={data.name} onChange={(e)=>setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={data.slug} onChange={(e)=>setData('slug', e.target.value)} /></div>
 <div><Label>Type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={data.type} onChange={(e)=>setData('type', e.target.value)}>{types.map((type)=><option key={type} value={type}>{type}</option>)}</select></div>
 <div className="grid gap-4 md:grid-cols-2"><div><Label>URL</Label><Input value={data.url} onChange={(e)=>setData('url', e.target.value)} /></div><div><Label>Feed URL</Label><Input value={data.feed_url} onChange={(e)=>setData('feed_url', e.target.value)} /></div></div>
 <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={data.description} onChange={(e)=>setData('description', e.target.value)} /></div>
 <div className="grid gap-4 md:grid-cols-3"><div><Label>Language</Label><Input value={data.language} onChange={(e)=>setData('language', e.target.value)} /></div><div><Label>Country code</Label><Input value={data.country_code} onChange={(e)=>setData('country_code', e.target.value)} /></div><div><Label>Trust level</Label><Input type="number" min={1} max={5} value={data.trust_level} onChange={(e)=>setData('trust_level', Number(e.target.value))} /></div></div>
 <label className="flex items-center gap-2"><input type="checkbox" checked={data.is_active} onChange={(e)=>setData('is_active', e.target.checked)} />Active</label>
 <div className="flex gap-2"><Button type="submit" disabled={processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.news-sources.index')}>Cancel</Link></Button></div></form></CardContent></Card></EditorLayout>;
}
