import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Option { id:number; name:string; code?:string; native_name?:string | null; flag_emoji?:string | null; }
interface Template { id:number; name:string; slug:string; description:string|null; language_id:number|null; location_id:number|null; edition_type:string|null; target_duration_seconds:number|null; intro_template:string|null; body_template:string|null; outro_template:string|null; is_active:boolean; sort_order:number; }
interface Props { template?:Template; languages:Option[]; locations:Option[]; editionTypes:string[]; }

export default function EditorialTemplateForm({ template, languages = [], locations = [], editionTypes = [] }: Props): JSX.Element {
    const isEdit = !!template;
    const form = useForm({
        name: template?.name ?? '', slug: template?.slug ?? '', description: template?.description ?? '', language_id: template?.language_id?.toString() ?? '',
        location_id: template?.location_id?.toString() ?? '', edition_type: template?.edition_type ?? '', target_duration_seconds: template?.target_duration_seconds?.toString() ?? '',
        intro_template: template?.intro_template ?? '', body_template: template?.body_template ?? '', outro_template: template?.outro_template ?? '',
        is_active: template?.is_active ?? true, sort_order: template?.sort_order ?? 0,
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        if (isEdit) form.put(route('editor.editorial-templates.update', template!.id));
        else form.post(route('editor.editorial-templates.store'));
    };

    return <EditorLayout><Head title={isEdit ? 'Edit Editorial Template' : 'Create Editorial Template'} /><AdminPageHeader title={isEdit ? 'Edit Editorial Template' : 'Create Editorial Template'} description="Reusable script template settings." />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div><Label>Name</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div>
            <div><Label>Slug</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div>
            <div><Label>Description</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.description} onChange={(e)=>form.setData('description', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>Editorial language</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.language_id} onChange={(e)=>form.setData('language_id', e.target.value)}><option value="">No template language</option>{languages.map((item)=><option key={item.id} value={item.id}>{item.flag_emoji} {item.native_name || item.name} ({item.code})</option>)}</select></div><div><Label>Location</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.location_id} onChange={(e)=>form.setData('location_id', e.target.value)}><option value="">Global</option>{locations.map((item)=><option key={item.id} value={item.id}>{item.name}</option>)}</select></div></div>
            <div className="grid gap-4 md:grid-cols-3"><div><Label>Edition type</Label><select className="w-full rounded-md border border-slate-300 px-3 py-2" value={form.data.edition_type} onChange={(e)=>form.setData('edition_type', e.target.value)}><option value="">Any</option>{editionTypes.map((type)=><option key={type} value={type}>{type}</option>)}</select></div><div><Label>Target duration</Label><Input type="number" value={form.data.target_duration_seconds} onChange={(e)=>form.setData('target_duration_seconds', e.target.value)} /></div><label className="flex items-center gap-2 pt-8"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />Active</label></div>
            <div><Label>Intro template</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.intro_template} onChange={(e)=>form.setData('intro_template', e.target.value)} /></div>
            <div><Label>Body template</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={8} value={form.data.body_template} onChange={(e)=>form.setData('body_template', e.target.value)} /></div>
            <div><Label>Outro template</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.outro_template} onChange={(e)=>form.setData('outro_template', e.target.value)} /></div>
            <div><Label>Sort order</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div>
            <div className="rounded border border-slate-200 bg-slate-50 p-3 text-sm">Template placeholders: {'{{edition_title}}'}, {'{{edition_type}}'}, {'{{location_name}}'}, {'{{language_code}}'}, {'{{news_items}}'}, {'{{date}}'}</div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>Save</Button><Button asChild variant="secondary"><Link href={route('editor.editorial-templates.index')}>Cancel</Link></Button></div>
        </form></CardContent></Card></EditorLayout>;
}
