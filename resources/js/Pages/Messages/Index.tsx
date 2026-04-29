import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import EditorLayout from '@/Layouts/EditorLayout';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, router, usePage } from '@inertiajs/react';

const Wrap = ({children}: any) => { const p:any=usePage().props.auth.user?.permissions ?? []; if(p.includes('admin.access')) return <AdminLayout>{children}</AdminLayout>; if(p.includes('editor.access')) return <EditorLayout>{children}</EditorLayout>; return <ViewerLayout>{children}</ViewerLayout>; }
export default function Index({ messages, filters, counts }: any){ const {t}=useTranslations(); const {formatDateTime}=useDateFormatter();
 const setBox=(box:string)=>router.get(route('messages.index'),{...filters,box},{preserveState:true});
 return <Wrap><Head title={t('Messages')} /><Card><CardHeader><CardTitle>{t('Messages')}</CardTitle>
 <div className='flex gap-2'><Button size='sm' variant={filters.box==='inbox'?'default':'outline'} onClick={()=>setBox('inbox')}>{t('Inbox')} ({counts.unread_inbox})</Button><Button size='sm' variant={filters.box==='sent'?'default':'outline'} onClick={()=>setBox('sent')}>{t('Sent')} ({counts.sent})</Button><Button size='sm' variant={filters.box==='archived'?'default':'outline'} onClick={()=>setBox('archived')}>{t('Archived messages')} ({counts.archived})</Button></div></CardHeader><CardContent className='space-y-3'>{messages.data.map((m:any)=><div key={m.id} className='rounded border p-3'><Link className='font-semibold underline' href={route('messages.show',m.id)}>{m.subject}</Link><p className='text-xs'>{m.message_type} · {formatDateTime(m.created_at)}</p>{m.related_link?<a className='text-xs underline' href={m.related_link.url}>{t('Open related item')}</a>:<p className='text-xs'>{t('No accessible related link')}</p>}<div className='mt-2 flex gap-2'>{m.recipient_id===usePage<any>().props.auth.user.id && <><Button size='sm' onClick={()=>router.post(route('messages.mark-read',m.id))}>{t('Mark as read')}</Button><Button size='sm' variant='outline' onClick={()=>router.post(route('messages.archive',m.id))}>{t('Archive message')}</Button></>}</div></div>)}</CardContent></Card></Wrap>}
