import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import UserIdentity from '@/Components/UserIdentity';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

interface MediaFileItem {
    id: number;
    original_name: string;
    filename: string;
    url: string | null;
    mime_type: string | null;
    media_type: string;
    size_bytes: number;
    human_size: string;
    collection: string | null;
    status: string;
    uploaded_by: { id: number; name: string; email: string | null; avatar_url?: string | null; initials?: string | null } | null;
    uploaded_by_id: number | null;
    created_at: string | null;
}

interface Props {
    mediaFiles: {
        data: MediaFileItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: {
        search?: string;
        media_type?: string;
        collection?: string;
        status?: string;
        uploaded_by?: number;
    };
    mediaTypes: string[];
    collections: string[];
    statuses: string[];
    uploaders: Array<{ id: number; name: string }>;
}

export default function MediaFilesIndex({ mediaFiles, filters, mediaTypes, collections, statuses, uploaders }: Props): JSX.Element {
    const { t } = useTranslations();

    const applyFilters = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        router.get(route('admin.media-files.index'), {
            search: formData.get('search') || undefined,
            media_type: formData.get('media_type') || undefined,
            collection: formData.get('collection') || undefined,
            status: formData.get('status') || undefined,
            uploaded_by: formData.get('uploaded_by') || undefined,
        }, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout>
            <Head title={t('Media Library')} />
            <AdminPageHeader title={t('Media Library')} description={t('Media files')} />

            <Card className="mb-4">
                <CardContent className="pt-6">
                    <form className="grid gap-4 md:grid-cols-5" onSubmit={applyFilters}>
                        <div>
                            <Label htmlFor="search">{t('Search')}</Label>
                            <Input id="search" name="search" defaultValue={filters.search ?? ''} placeholder={t('Original name')} />
                        </div>
                        <div>
                            <Label htmlFor="media_type">{t('Media type')}</Label>
                            <select id="media_type" name="media_type" defaultValue={filters.media_type ?? ''} className="w-full rounded-md border border-slate-300">
                                <option value="">{t('All types')}</option>
                                {mediaTypes.map((type) => <option key={type} value={type}>{t(type.charAt(0).toUpperCase() + type.slice(1))}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="collection">{t('Collection')}</Label>
                            <select id="collection" name="collection" defaultValue={filters.collection ?? ''} className="w-full rounded-md border border-slate-300">
                                <option value="">{t('Any')}</option>
                                {collections.map((collection) => <option key={collection} value={collection}>{collection}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="status">{t('Status')}</Label>
                            <select id="status" name="status" defaultValue={filters.status ?? ''} className="w-full rounded-md border border-slate-300">
                                <option value="">{t('All statuses')}</option>
                                {statuses.map((status) => <option key={status} value={status}>{t(status.charAt(0).toUpperCase() + status.slice(1))}</option>)}
                            </select>
                        </div>
                        <div>
                            <Label htmlFor="uploaded_by">{t('Uploaded by')}</Label>
                            <select id="uploaded_by" name="uploaded_by" defaultValue={filters.uploaded_by ?? ''} className="w-full rounded-md border border-slate-300">
                                <option value="">{t('Any')}</option>
                                {uploaders.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}
                            </select>
                        </div>
                        <div className="md:col-span-5 flex gap-2">
                            <Button type="submit">{t('Apply filters')}</Button>
                            <Button type="button" variant="outline" onClick={() => router.get(route('admin.media-files.index'))}>{t('Reset filters')}</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardContent className="overflow-x-auto pt-6">
                    <table className="w-full min-w-[900px] text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500">
                                <th className="px-2 pb-3">{t('Preview')}</th>
                                <th className="px-2 pb-3">{t('Original name')}</th>
                                <th className="px-2 pb-3">{t('Media type')}</th>
                                <th className="px-2 pb-3">{t('MIME type')}</th>
                                <th className="px-2 pb-3">{t('Size')}</th>
                                <th className="px-2 pb-3">{t('Collection')}</th>
                                <th className="px-2 pb-3">{t('Uploaded by')}</th>
                                <th className="px-2 pb-3">{t('Created at')}</th>
                                <th className="px-2 pb-3 text-right">{t('Actions')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {mediaFiles.data.length === 0 && (
                                <tr><td className="px-2 py-4 text-slate-500" colSpan={9}>{t('No media files found')}</td></tr>
                            )}
                            {mediaFiles.data.map((file) => (
                                <tr key={file.id} className="border-b border-slate-100">
                                    <td className="px-2 py-3">
                                        {file.media_type === 'image' && file.url ? <img src={file.url} alt={file.original_name} className="h-10 w-10 rounded object-cover" /> : <span className="text-xs text-slate-400">—</span>}
                                    </td>
                                    <td className="px-2 py-3 font-medium">{file.original_name}</td>
                                    <td className="px-2 py-3"><Badge variant="outline">{t(file.media_type.charAt(0).toUpperCase() + file.media_type.slice(1))}</Badge></td>
                                    <td className="px-2 py-3">{file.mime_type ?? '-'}</td>
                                    <td className="px-2 py-3">{file.human_size}</td>
                                    <td className="px-2 py-3">{file.collection ?? '-'}</td>
                                    <td className="px-2 py-3">{file.uploaded_by ? <UserIdentity user={file.uploaded_by} subtitle={file.uploaded_by.email} avatarSize="xs" /> : '-'}</td>
                                    <td className="px-2 py-3">{file.created_at ?? '-'}</td>
                                    <td className="px-2 py-3 text-right">
                                        <Button size="sm" asChild variant="outline"><Link href={route('admin.media-files.show', file.id)}>{t('Open file')}</Link></Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    <Pagination links={mediaFiles.links} />
                </CardContent>
            </Card>
        </AdminLayout>
    );
}
