import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Edition {
    id: number;
    title: string;
    edition_type: string;
    location: string | null;
    scheduled_for: string | null;
    language: string | null;
    status: string;
    target_duration_seconds: number | null;
    description: string | null;
}

interface NewsItemPivot {
    id: number;
    title: string;
    source: string | null;
    category: string | null;
    location: string | null;
    status: string;
    sort_order: number;
    editorial_angle: string | null;
    included_in_script: boolean;
}

interface Props {
    edition: Edition;
    newsItems: NewsItemPivot[];
    availableNewsItems: Array<{ id: number; title: string }>;
    scripts: Array<{ id: number; title: string; status: string; language: string | null; estimated_duration_seconds: number | null }>;
}

export default function Show({ edition, newsItems, availableNewsItems, scripts }: Props): JSX.Element {
    const [editingItemId, setEditingItemId] = useState<number | null>(null);

    const addForm = useForm({
        news_item_id: availableNewsItems[0]?.id?.toString() ?? '',
        sort_order: '0',
        editorial_angle: '',
        included_in_script: true,
    });

    const editForm = useForm({ sort_order: '0', editorial_angle: '', included_in_script: true });

    const submitAdd = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        addForm.post(route('editor.editions.news-items.store', edition.id), { preserveScroll: true });
    };

    const startEdit = (item: NewsItemPivot): void => {
        setEditingItemId(item.id);
        editForm.setData({
            sort_order: String(item.sort_order),
            editorial_angle: item.editorial_angle ?? '',
            included_in_script: item.included_in_script,
        });
    };

    const submitEdit = (e: FormEvent<HTMLFormElement>, newsItemId: number): void => {
        e.preventDefault();
        editForm.put(route('editor.editions.news-items.update', [edition.id, newsItemId]), { preserveScroll: true, onSuccess: () => setEditingItemId(null) });
    };

    return (
        <EditorLayout>
            <Head title={edition.title} />
            <AdminPageHeader title={edition.title} description="Edition detail." />

            <div className="space-y-6">
                <Card>
                    <CardContent className="space-y-3 pt-6 text-sm">
                        <p><strong>Type:</strong> {edition.edition_type}</p>
                        <p><strong>Location:</strong> {edition.location || '-'}</p>
                        <p><strong>Scheduled for:</strong> {edition.scheduled_for || '-'}</p>
                        <p><strong>Language:</strong> {edition.language || '-'}</p>
                        <p><strong>Status:</strong> {edition.status}</p>
                        <p><strong>Target duration:</strong> {edition.target_duration_seconds || '-'} seconds</p>
                        <p><strong>Description:</strong> {edition.description || '-'}</p>
                        <div className="flex gap-2">
                            <Button asChild>
                                <Link href={`${route('editor.scripts.create')}?edition_id=${edition.id}`}>Create Script</Link>
                            </Button>
                            <Button asChild variant="secondary"><Link href={route('editor.editions.index')}>Back</Link></Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <h2 className="mb-4 text-lg font-semibold">Add News Item to Edition</h2>
                        {availableNewsItems.length === 0 ? (
                            <p className="text-sm text-slate-500">All available news items are already selected for this edition.</p>
                        ) : (
                            <form onSubmit={submitAdd} className="grid gap-4 md:grid-cols-2">
                                <div className="md:col-span-2">
                                    <Label>News Item</Label>
                                    <select className="w-full rounded-md border border-slate-300 px-3 py-2" value={addForm.data.news_item_id} onChange={(e) => addForm.setData('news_item_id', e.target.value)}>
                                        {availableNewsItems.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <Label>Sort order</Label>
                                    <Input type="number" min={0} value={addForm.data.sort_order} onChange={(e) => addForm.setData('sort_order', e.target.value)} />
                                </div>
                                <div className="flex items-end">
                                    <label className="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" checked={addForm.data.included_in_script} onChange={(e) => addForm.setData('included_in_script', e.target.checked)} />
                                        Included in script
                                    </label>
                                </div>
                                <div className="md:col-span-2">
                                    <Label>Editorial angle</Label>
                                    <textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={addForm.data.editorial_angle} onChange={(e) => addForm.setData('editorial_angle', e.target.value)} />
                                </div>
                                <div className="md:col-span-2">
                                    <Button type="submit" disabled={addForm.processing}>Add</Button>
                                </div>
                            </form>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <h2 className="mb-4 text-lg font-semibold">Selected News Items</h2>
                        {newsItems.length === 0 ? (
                            <p className="text-slate-500">No selected news items yet.</p>
                        ) : (
                            <div className="space-y-4">
                                {newsItems.map((item) => (
                                    <div key={item.id} className="rounded-lg border border-slate-200 p-4">
                                        {editingItemId === item.id ? (
                                            <form onSubmit={(e) => submitEdit(e, item.id)} className="space-y-3">
                                                <p className="font-semibold">{item.title}</p>
                                                <div className="grid gap-3 md:grid-cols-2">
                                                    <div>
                                                        <Label>Sort order</Label>
                                                        <Input type="number" min={0} value={editForm.data.sort_order} onChange={(e) => editForm.setData('sort_order', e.target.value)} />
                                                    </div>
                                                    <label className="inline-flex items-center gap-2 pt-7 text-sm">
                                                        <input type="checkbox" checked={editForm.data.included_in_script} onChange={(e) => editForm.setData('included_in_script', e.target.checked)} />
                                                        Included in script
                                                    </label>
                                                </div>
                                                <div>
                                                    <Label>Editorial angle</Label>
                                                    <textarea className="w-full rounded-md border border-slate-300 px-3 py-2" rows={2} value={editForm.data.editorial_angle} onChange={(e) => editForm.setData('editorial_angle', e.target.value)} />
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button type="submit" size="sm">Save</Button>
                                                    <Button type="button" variant="secondary" size="sm" onClick={() => setEditingItemId(null)}>Cancel</Button>
                                                </div>
                                            </form>
                                        ) : (
                                            <>
                                                <p className="font-semibold">#{item.sort_order} {item.title}</p>
                                                <p className="text-sm text-slate-600">Source: {item.source || '-'} | Category: {item.category || '-'} | Location: {item.location || '-'} | Status: {item.status}</p>
                                                <p className="text-sm"><strong>Editorial angle:</strong> {item.editorial_angle || '-'}</p>
                                                <p className="text-sm"><strong>Included in script:</strong> {item.included_in_script ? 'Yes' : 'No'}</p>
                                                <div className="mt-2 flex gap-2">
                                                    <Button type="button" variant="secondary" size="sm" onClick={() => startEdit(item)}>Edit</Button>
                                                    <Button type="button" variant="destructive" size="sm" onClick={() => {
                                                        if (confirm('Remove this news item from the edition?')) {
                                                            editForm.delete(route('editor.editions.news-items.destroy', [edition.id, item.id]), { preserveScroll: true });
                                                        }
                                                    }}>Remove</Button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="pt-6">
                        <h2 className="mb-4 text-lg font-semibold">Edition Scripts</h2>
                        {scripts.length === 0 ? (
                            <p className="text-sm text-slate-500">No scripts created for this edition yet.</p>
                        ) : (
                            <ul className="space-y-2 text-sm">
                                {scripts.map((script) => (
                                    <li key={script.id} className="flex items-center justify-between rounded-md border border-slate-200 p-3">
                                        <div>
                                            <p className="font-medium">{script.title}</p>
                                            <p className="text-slate-500">{script.status} · {script.language || '-'} · {script.estimated_duration_seconds || '-'} seconds</p>
                                        </div>
                                        <Link href={route('editor.scripts.show', script.id)} className="text-cyan-700 underline">Open</Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </EditorLayout>
    );
}
