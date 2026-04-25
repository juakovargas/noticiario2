import AdminPageHeader from '@/Components/AdminPageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface Script {
    id: number;
    title: string;
    status: string;
    language: string | null;
    intro: string | null;
    body: string | null;
    outro: string | null;
    estimated_duration_seconds: number | null;
}

interface Props {
    script: Script;
    statuses: string[];
}

export default function Edit({ script, statuses }: Props): JSX.Element {
    const { data, setData, put, processing } = useForm({
        title: script.title,
        status: script.status,
        language: script.language || '',
        intro: script.intro || '',
        body: script.body || '',
        outro: script.outro || '',
        estimated_duration_seconds: script.estimated_duration_seconds?.toString() || '',
    });

    const submit = (e: FormEvent<HTMLFormElement>): void => {
        e.preventDefault();
        put(route('editor.scripts.update', script.id));
    };

    return (
        <EditorLayout>
            <Head title="Edit Script" />
            <AdminPageHeader title="Edit Script" description="Update script content manually." />
            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label>Title</Label>
                            <Input value={data.title} onChange={(e) => setData('title', e.target.value)} />
                        </div>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label>Status</Label>
                                <select
                                    className="w-full rounded-md border border-slate-300 px-3 py-2"
                                    value={data.status}
                                    onChange={(e) => setData('status', e.target.value)}
                                >
                                    {statuses.map((v) => (
                                        <option key={v} value={v}>
                                            {v}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label>Language</Label>
                                <Input value={data.language} onChange={(e) => setData('language', e.target.value)} />
                            </div>
                            <div>
                                <Label>Estimated duration</Label>
                                <Input
                                    type="number"
                                    value={data.estimated_duration_seconds}
                                    onChange={(e) => setData('estimated_duration_seconds', e.target.value)}
                                />
                            </div>
                        </div>
                        <div>
                            <Label>Intro</Label>
                            <textarea
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                                rows={3}
                                value={data.intro}
                                onChange={(e) => setData('intro', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label>Body</Label>
                            <textarea
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                                rows={8}
                                value={data.body}
                                onChange={(e) => setData('body', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label>Outro</Label>
                            <textarea
                                className="w-full rounded-md border border-slate-300 px-3 py-2"
                                rows={3}
                                value={data.outro}
                                onChange={(e) => setData('outro', e.target.value)}
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                            <Button asChild variant="secondary">
                                <Link href={route('editor.scripts.index')}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </EditorLayout>
    );
}
