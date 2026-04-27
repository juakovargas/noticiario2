import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option { id:number; name:string; code?:string; default_language_code?:string|null; type?:string; }
interface Provider { id:number; name:string; is_default:boolean; }
interface Prompt { id:number; name:string; type:string; }
interface RequestItem { id:number; title:string; location_id:number|null; news_category_id:number|null; language_id:number|null; edition_type:string|null; target_duration_seconds:number|null; ai_provider_id:number|null; ai_prompt_template_id:number|null; editorial_instructions:string|null; status:string; }
interface Props { requestItem?:RequestItem; providers:Provider[]; promptTemplates:Prompt[]; languages:Option[]; locations:Option[]; categories:Option[]; statuses:string[]; editionTypes:string[]; defaultProviderId:number|null; }

export default function EditorialRequestForm({ requestItem, providers = [], promptTemplates = [], languages = [], locations = [], categories = [], statuses = [], editionTypes = [], defaultProviderId = null }: Props): JSX.Element {
    const isEdit = !!requestItem;
    const form = useForm({
        title: requestItem?.title ?? '', location_id: requestItem?.location_id?.toString() ?? '', news_category_id: requestItem?.news_category_id?.toString() ?? '', language_id: requestItem?.language_id?.toString() ?? '', edition_type: requestItem?.edition_type ?? '', target_duration_seconds: requestItem?.target_duration_seconds?.toString() ?? '', ai_provider_id: requestItem?.ai_provider_id?.toString() ?? (defaultProviderId ? String(defaultProviderId) : ''), ai_prompt_template_id: requestItem?.ai_prompt_template_id?.toString() ?? '', editorial_instructions: requestItem?.editorial_instructions ?? '', status: requestItem?.status ?? 'draft',
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => { e.preventDefault(); if (isEdit) form.put(route('editor.editorial-requests.update', requestItem!.id)); else form.post(route('editor.editorial-requests.store')); };

    return <EditorLayout><Head title={isEdit ? 'Edit Editorial Request' : 'Create Editorial Request'} /><AdminPageHeader title={isEdit ? 'Edit Editorial Request' : 'Create Editorial Request'} description="Request AI-assisted editorial planning." /><Card><CardContent className="pt-6"><form className="space-y-4" onSubmit={submit}>
        <div><Label>Title</Label><Input value={form.data.title} onChange={(e)=>form.setData('title', e.target.value)} /></div>
        <div className="grid gap-4 md:grid-cols-3"><div><Label>Location</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">Any</option>{locations.map((item)=><option key={item.id} value={item.id}>{item.name}</option>)}</select></div><div><Label>Category</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.news_category_id} onChange={(e)=>form.setData('news_category_id', e.target.value)}><option value="">Any</option>{categories.map((item)=><option key={item.id} value={item.id}>{item.name}</option>)}</select></div><div><Label>Language</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">Any</option>{languages.map((item)=><option key={item.id} value={item.id}>{item.name} ({item.code})</option>)}</select></div></div>
        <div className="grid gap-4 md:grid-cols-3"><div><Label>Edition type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.edition_type} onChange={(e)=>form.setData('edition_type', e.target.value)}><option value="">Any</option>{editionTypes.map((item)=><option key={item} value={item}>{item}</option>)}</select></div><div><Label>Target duration</Label><Input type="number" value={form.data.target_duration_seconds} onChange={(e)=>form.setData('target_duration_seconds', e.target.value)} /></div><div><Label>Status</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.status} onChange={(e)=>form.setData('status', e.target.value)}>{statuses.map((item)=><option key={item} value={item}>{item}</option>)}</select></div></div>
        <div className="grid gap-4 md:grid-cols-2"><div><Label>AI Provider</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.ai_provider_id} onChange={(e)=>form.setData('ai_provider_id', e.target.value)}><option value="">Select provider</option>{providers.map((item)=><option key={item.id} value={item.id}>{item.name}{item.is_default ? ' (default)' : ''}</option>)}</select></div><div><Label>Prompt Template</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.ai_prompt_template_id} onChange={(e)=>form.setData('ai_prompt_template_id', e.target.value)}><option value="">Select template</option>{promptTemplates.map((item)=><option key={item.id} value={item.id}>{item.name} ({item.type})</option>)}</select></div></div>
        <div><Label>Editorial instructions</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={6} value={form.data.editorial_instructions} onChange={(e)=>form.setData('editorial_instructions', e.target.value)} /></div>
        <div className="flex gap-2"><Button type="submit" disabled={form.processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.editorial-requests.index')}>Cancel</Link></Button></div>
    </form></CardContent></Card></EditorLayout>;
}
