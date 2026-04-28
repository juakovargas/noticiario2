import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link, router } from '@inertiajs/react';

interface Item { id:number; name:string; display_name:string; parent:string|null; is_active:boolean; sort_order:number; color:string|null; }
interface Props { newsCategories:{data:Item[]; links:Array<{url:string|null; label:string; active:boolean}>}; }

export default function Index({ newsCategories }: Props): JSX.Element {
    const { t } = useTranslations();
    const destroy = (id:number):void => { if (window.confirm('Delete this category?')) router.delete(route('editor.news-categories.destroy', id)); };
    return <EditorLayout><Head title={t('News Categories')} /><AdminPageHeader helpKey="editor.newscategories.index" title={t('News Categories')} description="Manage thematic categories." actionLabel={t('Create')} actionHref={route('editor.news-categories.create')} />
        <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[760px] text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">{t('Name')}</th><th className="px-2 pb-3">Color</th><th className="px-2 pb-3">Parent</th><th className="px-2 pb-3">{t('Status')}</th><th className="px-2 pb-3">Sort</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{newsCategories.data.length ? newsCategories.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.display_name}</td><td className="px-2 py-3"><span className="inline-flex items-center gap-2"><span className="h-4 w-4 rounded-full border" style={{ backgroundColor: item.color || '#cbd5e1' }} />{item.color || '-'}</span></td><td className="px-2 py-3">{item.parent || '-'}</td><td className="px-2 py-3">{item.is_active ? t('Active') : t('Inactive')}</td><td className="px-2 py-3">{item.sort_order}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="secondary"><Link href={route('editor.news-categories.edit', item.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>{t('Delete')}</Button></div></td></tr>) : <tr><td colSpan={6} className="px-2 py-6 text-center text-slate-500">No categories yet.</td></tr>}</tbody></table><Pagination links={newsCategories.links} /></CardContent></Card></EditorLayout>;
}
