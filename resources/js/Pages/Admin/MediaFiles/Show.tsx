import AdminPageHeader from '@/Components/AdminPageHeader';
import UserIdentity from '@/Components/UserIdentity';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

interface MediaFile {
    id: number;
    original_name: string;
    filename: string;
    url: string | null;
    disk: string;
    directory: string | null;
    path: string;
    public_url: string | null;
    file_exists: boolean;
    storage_link_required: boolean;
    mime_type: string | null;
    extension: string | null;
    size_bytes: number;
    human_size: string;
    media_type: string;
    collection: string | null;
    visibility: string;
    status: string;
    title: string | null;
    alt_text: string | null;
    caption: string | null;
    description: string | null;
    width: number | null;
    height: number | null;
    duration_seconds: number | null;
    checksum: string | null;
    metadata: Record<string, unknown> | null;
    uploaded_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    created_at: string | null;
    updated_at: string | null;
}

export default function MediaFilesShow({ mediaFile }: { mediaFile: MediaFile }): JSX.Element {
    const { t } = useTranslations();
    const { data, setData, put, processing, errors } = useForm({
        title: mediaFile.title ?? '',
        alt_text: mediaFile.alt_text ?? '',
        caption: mediaFile.caption ?? '',
        description: mediaFile.description ?? '',
        status: mediaFile.status,
    });

    return (
        <AdminLayout>
            <Head title={t('Media file')} />
            <AdminPageHeader title={t('Media file')} description={mediaFile.original_name} />
            <Card className="mb-4"><CardContent className="pt-6">
                {mediaFile.media_type === 'image' && mediaFile.url && <img src={mediaFile.url} alt={mediaFile.alt_text ?? mediaFile.original_name} className="max-h-96 rounded border" />}
                <dl className="mt-4 grid grid-cols-1 gap-2 text-sm md:grid-cols-2">
                    <div><dt className="font-semibold">{t('Original name')}</dt><dd>{mediaFile.original_name}</dd></div>
                    <div><dt className="font-semibold">{t('File name')}</dt><dd>{mediaFile.filename}</dd></div>
                    <div><dt className="font-semibold">{t('Path')}</dt><dd>{mediaFile.path}</dd></div>
                    <div><dt className="font-semibold">{t('Disk')}</dt><dd>{mediaFile.disk}</dd></div>
                    <div><dt className="font-semibold">{t('Directory')}</dt><dd>{mediaFile.directory ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('MIME type')}</dt><dd>{mediaFile.mime_type ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Extension')}</dt><dd>{mediaFile.extension ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('File size')}</dt><dd>{mediaFile.human_size}</dd></div>
                    <div><dt className="font-semibold">{t('Width')}</dt><dd>{mediaFile.width ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Height')}</dt><dd>{mediaFile.height ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Duration')}</dt><dd>{mediaFile.duration_seconds ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Checksum')}</dt><dd>{mediaFile.checksum ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Collection')}</dt><dd>{mediaFile.collection ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Visibility')}</dt><dd>{t(mediaFile.visibility === 'public' ? 'Public' : 'Private')}</dd></div>
                    <div><dt className="font-semibold">{t('Status')}</dt><dd>{t(mediaFile.status.charAt(0).toUpperCase() + mediaFile.status.slice(1))}</dd></div>
                    <div><dt className="font-semibold">{t('Uploaded by')}</dt><dd>{mediaFile.uploaded_by ? <UserIdentity user={mediaFile.uploaded_by} subtitle={mediaFile.uploaded_by.email} avatarSize="xs" /> : '-'}</dd></div>
                    <div><dt className="font-semibold">{t('Public URL')}</dt><dd className="break-all">{mediaFile.public_url ?? '-'}</dd></div>
                    <div><dt className="font-semibold">{t('File exists')}</dt><dd>{mediaFile.file_exists ? t('Yes') : t('File missing')}</dd></div>
                </dl>
                {mediaFile.storage_link_required && !mediaFile.file_exists && (
                    <p className="mt-3 text-xs text-amber-700">{t('Storage link required')}: {t('Run php artisan storage:link')}</p>
                )}
            </CardContent></Card>

            <Card><CardContent className="pt-6">
                <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); put(route('admin.media-files.update', mediaFile.id)); }}>
                    <div><Label htmlFor="title">{t('Title')}</Label><Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} />{errors.title && <p className="text-xs text-rose-600">{errors.title}</p>}</div>
                    <div><Label htmlFor="alt_text">{t('Alt text')}</Label><Input id="alt_text" value={data.alt_text} onChange={(e) => setData('alt_text', e.target.value)} />{errors.alt_text && <p className="text-xs text-rose-600">{errors.alt_text}</p>}</div>
                    <div><Label htmlFor="caption">{t('Caption')}</Label><Input id="caption" value={data.caption} onChange={(e) => setData('caption', e.target.value)} /></div>
                    <div><Label htmlFor="description">{t('Description')}</Label><textarea id="description" className="w-full rounded-md border border-slate-300" rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} /></div>
                    <div><Label htmlFor="status">{t('Status')}</Label><select id="status" className="w-full rounded-md border border-slate-300" value={data.status} onChange={(e) => setData('status', e.target.value)}><option value="active">{t('Active')}</option><option value="archived">{t('Archived')}</option><option value="deleted">{t('Deleted')}</option></select></div>
                    <div className="flex gap-2"><Button type="submit" disabled={processing}>{t('Save')}</Button><Button asChild type="button" variant="outline"><Link href={route('admin.media-files.index')}>{t('Back')}</Link></Button>{mediaFile.public_url && <Button type="button" variant="outline" onClick={() => navigator.clipboard.writeText(mediaFile.public_url ?? '')}>{t('Copy URL')}</Button>}</div>
                </form>
            </CardContent></Card>
        </AdminLayout>
    );
}
