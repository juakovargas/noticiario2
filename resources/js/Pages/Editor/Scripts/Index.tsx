import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
interface Item { id:number; title:string; edition:string|null; status:string; language:string|null; estimated_duration_seconds:number|null; approved_at:string|null; }
interface Props { scripts:{data:Item[]; links:Array<{url:string|null; label:string; active:boolean}>}; }
export default function Index({ scripts }: Props): JSX.Element {
 const destroy=(id:number)=>{if(window.confirm('Delete this script?')) router.delete(route('editor.scripts.destroy', id));};
 return <EditorLayout><Head title="Scripts" /><AdminPageHeader title="Scripts" description="Editorial scripts for editions." actionLabel="Create script" actionHref={route('editor.scripts.create')} />
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[900px] text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">Title</th><th className="px-2 pb-3">Edition</th><th className="px-2 pb-3">Status</th><th className="px-2 pb-3">Language</th><th className="px-2 pb-3">Estimated duration</th><th className="px-2 pb-3">Approved at</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{scripts.data.length ? scripts.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.title}</td><td className="px-2 py-3">{item.edition || '-'}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3">{item.language || '-'}</td><td className="px-2 py-3">{item.estimated_duration_seconds || '-'}</td><td className="px-2 py-3">{item.approved_at || '-'}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.scripts.show', item.id)}>View</Link></Button><Button asChild size="sm" variant="secondary"><Link href={route('editor.scripts.edit', item.id)}>Edit</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={7} className="px-2 py-6 text-center text-slate-500">No scripts yet.</td></tr>}</tbody></table><Pagination links={scripts.links} /></CardContent></Card></EditorLayout>;
}
