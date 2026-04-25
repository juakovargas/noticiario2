import AdminPageHeader from '@/Components/AdminPageHeader';
import Pagination from '@/Components/Pagination';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { countryCodeToFlagEmoji } from '@/lib/flags';
import { Head, Link, router } from '@inertiajs/react';
interface Item { id:number; title:string; source:string|null; category:string|null; location:{name:string; country_code:string|null}|null; status:string; published_at:string|null; editorial_priority:number; }
interface Props { newsItems:{data:Item[]; links:Array<{url:string|null; label:string; active:boolean}>}; }
export default function Index({ newsItems }: Props): JSX.Element {
 const { t } = useTranslations();
 const destroy=(id:number)=>{if(window.confirm('Delete this news item?')) router.delete(route('editor.news-items.destroy', id));};
 return <EditorLayout><Head title={t('News Items')} /><AdminPageHeader title={t('News Items')} description="Manage collected and manual news." actionLabel={t('Create')} actionHref={route('editor.news-items.create')} />
 <Card><CardContent className="overflow-x-auto pt-6"><table className="w-full min-w-[900px] text-sm"><thead><tr className="border-b border-slate-200 text-slate-500"><th className="px-2 pb-3">{t('Title')}</th><th className="px-2 pb-3">{t('Source')}</th><th className="px-2 pb-3">{t('Category')}</th><th className="px-2 pb-3">{t('Location')}</th><th className="px-2 pb-3">{t('Status')}</th><th className="px-2 pb-3">Published at</th><th className="px-2 pb-3">Priority</th><th className="px-2 pb-3 text-right">{t('Actions')}</th></tr></thead><tbody>{newsItems.data.length ? newsItems.data.map((item)=><tr key={item.id} className="border-b border-slate-100"><td className="px-2 py-3 font-medium">{item.title}</td><td className="px-2 py-3">{item.source || '-'}</td><td className="px-2 py-3">{item.category || '-'}</td><td className="px-2 py-3">{countryCodeToFlagEmoji(item.location?.country_code)} {item.location?.name || '-'}</td><td className="px-2 py-3">{item.status}</td><td className="px-2 py-3">{item.published_at || '-'}</td><td className="px-2 py-3">{item.editorial_priority}</td><td className="px-2 py-3"><div className="flex justify-end gap-2"><Button asChild size="sm" variant="outline"><Link href={route('editor.news-items.show', item.id)}>{t('View')}</Link></Button><Button asChild size="sm" variant="secondary"><Link href={route('editor.news-items.edit', item.id)}>{t('Edit')}</Link></Button><Button size="sm" variant="destructive" onClick={()=>destroy(item.id)}>{t('Delete')}</Button></div></td></tr>) : <tr><td colSpan={8} className="px-2 py-6 text-center text-slate-500">No news items yet.</td></tr>}</tbody></table><Pagination links={newsItems.links} /></CardContent></Card></EditorLayout>;
}
