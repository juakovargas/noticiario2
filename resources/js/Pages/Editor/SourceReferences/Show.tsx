import AdminPageHeader from '@/Components/AdminPageHeader';
import UserIdentity from '@/Components/UserIdentity';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, useForm } from '@inertiajs/react';

interface SourceReference { id:number; title:string|null; source_name:string|null; source_url:string|null; source_type:string; verification_status:string; trust_level:number|null; notes:string|null; checked_by:{ id:number; name:string; email:string | null; avatar_url?:string|null; initials?:string|null }|null; checked_at:string|null; script:{id:number;title:string}|null; news_item:{id:number;title:string}|null; script_review_item:{id:number;title:string|null}|null; editorial_schedule_run:{id:number;status:string;scheduled_for:string|null}|null; }
interface Props { sourceReference: SourceReference; verificationStatuses:string[]; sourceTypes:string[] }

export default function Show({ sourceReference, verificationStatuses, sourceTypes }: Props): JSX.Element {
    const { t } = useTranslations();
    const { formatDateTime } = useDateFormatter();
    const form = useForm({ title: sourceReference.title ?? '', source_name: sourceReference.source_name ?? '', source_url: sourceReference.source_url ?? '', source_type: sourceReference.source_type, verification_status: sourceReference.verification_status, trust_level: sourceReference.trust_level?.toString() ?? '', notes: sourceReference.notes ?? '' });

    return <EditorLayout>
        <Head title={t('Source Reference')} />
        <AdminPageHeader title={t('Source Reference')} description={t('Source verification')} />
        <Card className="mb-4"><CardHeader><CardTitle>{t('Source verification')}</CardTitle></CardHeader><CardContent>
            <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); form.put(route('editor.source-references.update', sourceReference.id));}}>
                <div><label className="mb-1 block text-sm font-medium">{t('Title')}</label><input className="w-full rounded border px-3 py-2" value={form.data.title} onChange={(e)=>form.setData('title', e.target.value)} /></div>
                <div><label className="mb-1 block text-sm font-medium">{t('Source name')}</label><input className="w-full rounded border px-3 py-2" value={form.data.source_name} onChange={(e)=>form.setData('source_name', e.target.value)} /></div>
                <div><label className="mb-1 block text-sm font-medium">{t('Source URL')}</label><input className="w-full rounded border px-3 py-2" value={form.data.source_url} onChange={(e)=>form.setData('source_url', e.target.value)} /></div>
                <div className="grid gap-3 md:grid-cols-3">
                    <div><label className="mb-1 block text-sm font-medium">{t('Source type')}</label><select className="w-full rounded border px-3 py-2" value={form.data.source_type} onChange={(e)=>form.setData('source_type', e.target.value)}>{sourceTypes.map((item)=><option key={item} value={item}>{item}</option>)}</select></div>
                    <div><label className="mb-1 block text-sm font-medium">{t('Verification status')}</label><select className="w-full rounded border px-3 py-2" value={form.data.verification_status} onChange={(e)=>form.setData('verification_status', e.target.value)}>{verificationStatuses.map((item)=><option key={item} value={item}>{item}</option>)}</select></div>
                    <div><label className="mb-1 block text-sm font-medium">{t('Trust level')}</label><input className="w-full rounded border px-3 py-2" value={form.data.trust_level} onChange={(e)=>form.setData('trust_level', e.target.value)} /></div>
                </div>
                <div><label className="mb-1 block text-sm font-medium">{t('Source notes')}</label><textarea className="w-full rounded border px-3 py-2" rows={4} value={form.data.notes} onChange={(e)=>form.setData('notes', e.target.value)} /></div>
                <div className="text-xs text-slate-500">{t('Checked by')}: {sourceReference.checked_by ? <UserIdentity user={sourceReference.checked_by} subtitle={sourceReference.checked_by.email} avatarSize="xs" className="inline-flex ml-1" /> : '-'} · {t('Checked at')}: {formatDateTime(sourceReference.checked_at)}</div>
                <div className="flex flex-wrap gap-2"><Button type="submit">{t('Save verification')}</Button>{sourceReference.source_url ? <Button asChild variant="outline"><a href={sourceReference.source_url} target="_blank" rel="noreferrer">{t('Open source')}</a></Button> : null}<Button asChild variant="secondary"><Link href={route('editor.source-references.index')}>{t('Back to source references')}</Link></Button></div>
            </form>
        </CardContent></Card>

        <Card><CardHeader><CardTitle>{t('Linked content')}</CardTitle></CardHeader><CardContent className="text-sm space-y-2">
            <p><strong>{t('Linked script')}:</strong> {sourceReference.script ? <Link className="text-cyan-700 underline" href={route('editor.scripts.show', sourceReference.script.id)}>{sourceReference.script.title}</Link> : '-'}</p>
            <p><strong>{t('Linked news item')}:</strong> {sourceReference.news_item ? <Link className="text-cyan-700 underline" href={route('editor.news-items.show', sourceReference.news_item.id)}>{sourceReference.news_item.title}</Link> : '-'}</p>
            <p><strong>{t('Linked review item')}:</strong> {sourceReference.script_review_item ? <Link className="text-cyan-700 underline" href={route('editor.scripts.review', sourceReference.script?.id)}>{`#${sourceReference.script_review_item.id} ${sourceReference.script_review_item.title ?? ''}`}</Link> : '-'}</p>
            <p><strong>{t('Editorial Run')}:</strong> {sourceReference.editorial_schedule_run ? <Link className="text-cyan-700 underline" href={route('editor.editorial-schedule-runs.show', sourceReference.editorial_schedule_run.id)}>{`#${sourceReference.editorial_schedule_run.id}`}</Link> : '-'}</p>
        </CardContent></Card>
    </EditorLayout>;
}
