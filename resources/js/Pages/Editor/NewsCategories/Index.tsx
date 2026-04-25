import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Item { id:number; name:string; parent:string|null; is_active:boolean; sort_order:number; }
interface Props { newsCategories:{data:Item[]; links:Array<{url:string|null; label:string; active:boolean}>}; }

export default function Index({ newsCategories }: Props): JSX.Element {
    const destroy = (id:number):void => { if (window.confirm('Delete this category?')) router.delete(route('editor.news-categories.destroy', id)); };
    return <EditorLayout><Head title="News Categories" /><AdminPageHeader title="News Categories" description="Manage thematic categories." actionLabel="Create category" actionHref={route('editor.news-categories.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[700px] text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">Name</th><th className="px-2 pb-3">Parent</th><th className="px-2 pb-3">Active</th><th className="px-2 pb-3">Sort order</th><th className="px-2 pb-3 text-right">Actions</th></tr></thead><tbody>{newsCategories.data.length ? newsCategories.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.name}</td><td className="px-2 py-3">{item.parent || '-'}</td><td className="px-2 py-3">{item.is_active ? 'Yes' : 'No'}</td><td className="px-2 py-3">{item.sort_order}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.news-categories.edit', item.id)}>Edit</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>Delete</Button></div></td></tr>) : <tr><td colSpan={5} className="px-2 py-6 text-center text-slate-500">No categories yet.</td></tr>}</tbody></table><Pagination links={newsCategories.links} /></CardContent></Card></EditorLayout>;
}
