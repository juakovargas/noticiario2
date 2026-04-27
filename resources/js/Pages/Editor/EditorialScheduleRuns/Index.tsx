import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useDateFormatter } from '@/lib/useDateFormatter';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';

export default function Index({ runs }: any): JSX.Element {
  const { formatDateTime } = useDateFormatter();
  return <EditorLayout><Head title="Editorial Runs" /><AdminPageHeader title="Editorial Runs" description="Manual AI run desk." /><Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[800px] text-sm"><thead><tr className="border-b"><th className="px-2 pb-3 text-left">ID</th><th className="px-2 pb-3 text-left">Schedule</th><th className="px-2 pb-3 text-left">Scheduled for</th><th className="px-2 pb-3 text-left">Status</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{runs.data.length ? runs.data.map((item:any)=><tr key={item.id} className="border-b"><td className="px-2 py-3">#{item.id}</td><td className="px-2 py-3">{item.schedule?.name || '-'}</td><td className="px-2 py-3">{formatDateTime(item.scheduled_for)}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3 text-right"><Button asChild size="sm" variant="outline"><Link href={route('editor.editorial-schedule-runs.show', item.id)}>Open</Link></Button></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center">No runs found.</td></tr>}</tbody></table><Pagination links={runs.links} /></CardContent></Card></EditorLayout>;
}
