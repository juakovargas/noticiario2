import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function Form({ profile }: any): JSX.Element {
    const isEdit = !!profile;
    const { t } = useTranslations();
    const form = useForm({
        name: profile?.name ?? '', slug: profile?.slug ?? '', description: profile?.description ?? '',
        happiness_level: profile?.happiness_level ?? 5, optimism_level: profile?.optimism_level ?? 5, seriousness_level: profile?.seriousness_level ?? 5,
        humor_level: profile?.humor_level ?? 0, irony_level: profile?.irony_level ?? 0, formality_level: profile?.formality_level ?? 5,
        negativity_tolerance: profile?.negativity_tolerance ?? 5, controversy_tolerance: profile?.controversy_tolerance ?? 5, source_strictness_level: profile?.source_strictness_level ?? 7,
        target_audience: profile?.target_audience ?? '', presenter_style: profile?.presenter_style ?? '', forbidden_topics_text: (profile?.forbidden_topics ?? []).join(', '), preferred_topics_text: (profile?.preferred_topics ?? []).join(', '),
        style_instructions: profile?.style_instructions ?? '', fact_checking_instructions: profile?.fact_checking_instructions ?? '', output_instructions: profile?.output_instructions ?? '',
        is_active: profile?.is_active ?? true, is_default: profile?.is_default ?? false, sort_order: profile?.sort_order ?? 0,
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        const payload = {
            ...form.data,
            forbidden_topics: form.data.forbidden_topics_text.split(',').map((x: string) => x.trim()).filter(Boolean),
            preferred_topics: form.data.preferred_topics_text.split(',').map((x: string) => x.trim()).filter(Boolean),
        };
        form.transform(() => payload);
        if (isEdit) form.put(route('admin.prompt-profiles.update', profile.id));
        else form.post(route('admin.prompt-profiles.store'));
    };

    const levelFields = [
        ['happiness_level', t('Happiness level')], ['optimism_level', t('Optimism level')], ['seriousness_level', t('Seriousness level')], ['humor_level', t('Humor level')],
        ['irony_level', t('Irony level')], ['formality_level', t('Formality level')], ['negativity_tolerance', t('Negativity tolerance')],
        ['controversy_tolerance', t('Controversy tolerance')], ['source_strictness_level', t('Source strictness level')],
    ] as const;

    return <AdminLayout><Head title={isEdit ? t('Edit Prompt Profile') : t('Create Prompt Profile')} />
        <AdminPageHeader title={isEdit ? t('Edit Prompt Profile') : t('Create Prompt Profile')} description={t('External AI workflow')} />
        <Card><CardContent className="pt-6"><form onSubmit={submit} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Name')}</Label><Input value={form.data.name} onChange={(e)=>form.setData('name', e.target.value)} /></div><div><Label>{t('Slug')}</Label><Input value={form.data.slug} onChange={(e)=>form.setData('slug', e.target.value)} /></div></div>
            <div><Label>{t('Description')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={form.data.description} onChange={(e)=>form.setData('description', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-3">{levelFields.map(([key,label])=><div key={key}><Label>{label}</Label><Input type="number" min={0} max={10} value={(form.data as any)[key]} onChange={(e)=>form.setData(key as any, Number(e.target.value))} /><p className="text-xs text-slate-500">0-10</p></div>)}</div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Target audience')}</Label><Input value={form.data.target_audience} onChange={(e)=>form.setData('target_audience', e.target.value)} /></div><div><Label>{t('Presenter style')}</Label><Input value={form.data.presenter_style} onChange={(e)=>form.setData('presenter_style', e.target.value)} /></div></div>
            <div className="grid gap-4 md:grid-cols-2"><div><Label>{t('Forbidden topics')}</Label><Input value={form.data.forbidden_topics_text} onChange={(e)=>form.setData('forbidden_topics_text', e.target.value)} /></div><div><Label>{t('Preferred topics')}</Label><Input value={form.data.preferred_topics_text} onChange={(e)=>form.setData('preferred_topics_text', e.target.value)} /></div></div>
            <div><Label>{t('Style instructions')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.style_instructions} onChange={(e)=>form.setData('style_instructions', e.target.value)} /></div>
            <div><Label>{t('Fact-checking instructions')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.fact_checking_instructions} onChange={(e)=>form.setData('fact_checking_instructions', e.target.value)} /></div>
            <div><Label>{t('Output instructions')}</Label><textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={3} value={form.data.output_instructions} onChange={(e)=>form.setData('output_instructions', e.target.value)} /></div>
            <div className="grid gap-4 md:grid-cols-3"><label className="flex items-center gap-2 pt-7"><input type="checkbox" checked={form.data.is_active} onChange={(e)=>form.setData('is_active', e.target.checked)} />{t('Active')}</label><label className="flex items-center gap-2 pt-7"><input type="checkbox" checked={form.data.is_default} onChange={(e)=>form.setData('is_default', e.target.checked)} />{t('Default')}</label><div><Label>{t('Sort order')}</Label><Input type="number" value={form.data.sort_order} onChange={(e)=>form.setData('sort_order', Number(e.target.value))} /></div></div>
            <div className="flex gap-2"><Button type="submit" disabled={form.processing}>{t('Save')}</Button><Button asChild variant="secondary"><Link href={route('admin.prompt-profiles.index')}>{t('Cancel')}</Link></Button></div>
        </form></CardContent></Card>
    </AdminLayout>;
}
