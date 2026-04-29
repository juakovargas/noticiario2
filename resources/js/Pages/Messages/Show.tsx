import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import AdminLayout from '@/Layouts/AdminLayout';
import EditorLayout from '@/Layouts/EditorLayout';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link, usePage } from '@inertiajs/react';
const Wrap = ({children}: any) => { const p:any=usePage().props.auth.user?.permissions ?? []; if(p.includes('admin.access')) return <AdminLayout>{children}</AdminLayout>; if(p.includes('editor.access')) return <EditorLayout>{children}</EditorLayout>; return <ViewerLayout>{children}</ViewerLayout>; }
export default function Show({ message, relatedLink, recipientReason }: any){ const {t}=useTranslations(); const {formatDateTime}=useDateFormatter();
return <Wrap><Head title={t('Message')} /><Card><CardHeader><CardTitle>{message.subject}</CardTitle></CardHeader><CardContent><p className='text-xs'>{t('Sender')}: {message.sender?.name ?? '-'} · {t('Sent to')}: {message.recipient?.name ?? '-'} · {formatDateTime(message.created_at)}</p><p className='text-xs'>{t('Recipient reason')}: {recipientReason ?? '-'}</p><p className='mt-4 whitespace-pre-wrap'>{message.body}</p>{relatedLink?<a className='mt-4 inline-block underline' href={relatedLink.url}>{t('Open related item')}</a>:<p className='mt-4 text-xs'>{t('No accessible related link')}</p>}<Link className='mt-4 block underline' href={route('messages.index')}>{t('Open messages')}</Link></CardContent></Card></Wrap> }
