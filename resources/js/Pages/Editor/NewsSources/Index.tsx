import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';
interface Item { id:number; name:string; type:string; url:string|null; feed_url:string|null; language:string|null; is_active:boolean; trust_level:number; }
interface Props { newsSources:{data:Item[]; links:Array<{url:string|null; label:string; active:boolean}>}; }
export default function Index({ newsSources }: Props): JSX.Element {
 const destroy=(id:number)=>{if(window.confirm('Delete this source?')) router.delete(route('editor.news-sources.destroy', id));};
 return <EditorLayout><Head title="News Sources" /><AdminPageHeader title="News Sources" description="Manage source catalog." actionLabel="Create source" actionHref={route('editor.news-sources.create')} />
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[700px] text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">Name</th><th className="px-2 pb-3">Type</th><th className="px-2 pb-3">URL/feed</th><th className="px-2 pb-3">Language</th><th className="px-2 pb-3">Active</th><th className="px-2 pb-3">Trust level</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{newsSources.data.length ? newsSources.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.name}</td><td className="px-2 py-3">{item.type}</td><td className="px-2 py-3">{item.feed_url || item.url || '-'}</td><td className="px-2 py-3">{item.language || '-'}</td><td className="px-2 py-3">{item.is_active ? 'Yes' : 'No'}</td><td className="px-2 py-3">{item.trust_level}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.news-sources.edit', item.id)}>Edit</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={7} className="px-2 py-6 text-center text-slate-500">No sources yet.</td></tr>}</tbody></table><Pagination links={newsSources.links} /></CardContent></Card></EditorLayout>;
}
