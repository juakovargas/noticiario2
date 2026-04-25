import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent } from '@/Components/ui/card';
import EditorLayout from '@/Layouts/EditorLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
interface NewsItem { id:number; title:string; summary:string|null; body:string|null; source:string|null; category:string|null; location:{name:string; country_code:string|null}|null; source_url:string|null; status:string; published_at:string|null; collected_at:string|null; }
interface Props { newsItem:NewsItem; }
export default function Show({ newsItem }: Props): JSX.Element {
 return <EditorLayout><Head title={newsItem.title} /><AdminPageHeader title={newsItem.title} description="News item detail." /><Card><CardContent className="space-y-3 pt-6 text-sm"><p><strong>Summary:</strong> {newsItem.summary || '-'}</p><p><strong>Body:</strong> {newsItem.body || '-'}</p><p><strong>Source:</strong> {newsItem.source || '-'}</p><p><strong>Category:</strong> {newsItem.category || '-'}</p><p><strong>Location:</strong> {newsItem.location?.name || '-'}</p><p><strong>Source URL:</strong> {newsItem.source_url || '-'}</p><p><strong>Status:</strong> {newsItem.status}</p><p><strong>Published at:</strong> {newsItem.published_at || '-'}</p><p><strong>Collected at:</strong> {newsItem.collected_at || '-'}</p><Button asChild variant="secondary"><Link href={route('editor.news-items.index')}>Back</Link></Button></CardContent></Card></EditorLayout>;
}
