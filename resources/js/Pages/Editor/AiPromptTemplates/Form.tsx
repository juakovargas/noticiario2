import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option { id:number; name:string; code?:string; native_name?:string|null; flag_emoji?:string|null; }
interface Template { id:number; name:string; slug:string; type:string; description:string|null; system_prompt:string|null; user_prompt:string; language_id:number|null; location_id:number|null; news_category_id:number|null; edition_type:string|null; expected_output_format:string; is_active:boolean; sort_order:number; }
interface Props { template?:Template; languages:Option[]; locations:Option[]; categories:Option[]; types:string[]; outputFormats:string[]; }

export default function AiPromptTemplateForm({ template, languages = [], locations = [], categories = [], types = [], outputFormats = [] }: Props): JSX.Element {
    const isEdit = !!template;
    const form = useForm({ name: template?.name ?? '', slug: template?.slug ?? '', type: template?.type ?? 'editorial_research', description: template?.description ?? '', system_prompt: template?.system_prompt ?? '', user_prompt: template?.user_prompt ?? '', language_id: template?.language_id?.toString() ?? '', location_id: template?.location_id?.toString() ?? '', news_category_id: template?.news_category_id?.toString() ?? '', edition_type: template?.edition_type ?? '', expected_output_format: template?.expected_output_format ?? 'text', is_active: template?.is_active ?? true, sort_order: template?.sort_order ?? 0 });
    const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); if (isEdit) form.put(route('editor.ai-prompt-templates.update', template!.id)); else form.post(route('editor.ai-prompt-templates.store')); };

    return <EditorLayout><Head title={isEdit ? 'Edit Prompt Template' : 'Create Prompt Template'} /><AdminPageHeader title={isEdit ? 'Edit Prompt Template' : 'Create Prompt Template'} description="Reusable AI prompts for editorial workflow." />
        <Card><CardContent className="pt-6"><form className="space-y-4" onSubmit={submit}>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>Name</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>Slug</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>Type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.type} onChange={(e)=>form.setData('type', e.target.value)}>{types.map((item)=><option key={item} value={item}>{item}</option>)}</select></div><div><Label>Expected output format</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.expected_output_format} onChange={(e)=>form.setData('expected_output_format', e.target.value)}>{outputFormats.map((item)=><option key={item} value={item}>{item}</option>)}</select></div><div><Label>Edition type</Label><Input value={form.data.edition_type} onChange={(e)=>form.setData('edition_type', e.target.value)} /></div></div>
            <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.description} onChange={(e)=>form.setData('description', e.target.value)} /></div>
            <div><Label>System prompt</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={6} value={form.data.system_prompt} onChange={(e)=>form.setData('system_prompt', e.target.value)} /></div>
            <div><Label>User prompt</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={8} value={form.data.user_prompt} onChange={(e)=>form.setData('user_prompt', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>Language</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">Any</option>{languages.map((item)=><option key={item.id} value={item.id}>{item.flag_emoji} {item.native_name || item.name}</option>)}</select></div><div><Label>Location</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">Any</option>{locations.map((item)=><option key={item.id} value={item.id}>{item.name}</option>)}</select></div><div><Label>Category</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.news_category_id} onChange={(e)=>form.setData('news_category_id', e.target.value)}><option value="">Any</option>{categories.map((item)=><option key={item.id} value={item.id}>{item.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-2"><label className="flex items-center gap-2"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />Active</label><div><Label>Sort order</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div></div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.ai-prompt-templates.index')}>Cancel</Link></Button></div>
        </form></CardContent></Card></EditorLayout>;
}
