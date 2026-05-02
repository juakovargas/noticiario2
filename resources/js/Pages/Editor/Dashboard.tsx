import AdminPageHeader from '@/Components/AdminPageHeader';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ summary, mapOverview, aiProviderStatus, actionableQueue, scheduledBulletins, coverageOverview, editorialAlerts, latestExecutions }: any) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const cards = [['Informativos activos', summary.activeBulletins], ['Tareas pendientes', summary.pendingTasks], ['Horarios vencidos', summary.overdueSchedules], ['Ejecuciones fallidas hoy', summary.failedRunsToday], ['Guiones pendientes de revisión', summary.scriptsPendingReview], ['Fuentes pendientes de verificación', summary.sourcesPendingVerification], ['Listo para producción', summary.readyForProduction]];

  return <EditorLayout><Head title={t('Mesa de trabajo editorial')} />
    <AdminPageHeader helpKey="editor.dashboard" title={t('Mesa de trabajo editorial')} description={t('Centro diario de operaciones editoriales')} />
    <div className='grid gap-3 md:grid-cols-2 xl:grid-cols-4'>{cards.map(([k,v]) => <Card key={String(k)}><CardHeader><CardTitle className='text-sm'>{t(String(k))}</CardTitle></CardHeader><CardContent className='text-2xl font-semibold'>{v ?? 0}</CardContent></Card>)}</div>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Informativos programados')}</CardTitle></CardHeader><CardContent className='grid gap-3 md:grid-cols-2'>
      {['on','off','incomplete','attention'].map((state) => <div key={state} className='rounded border p-3'><div className='mb-2 font-semibold'>{t(state === 'on' ? 'Encendidos' : state === 'off' ? 'Apagados' : state === 'incomplete' ? 'Incompletos' : 'Con incidencias')}</div>{(scheduledBulletins[state] || []).slice(0, 6).map((b:any) => <div key={b.id} className='mb-2 rounded bg-slate-50 p-2 text-xs dark:bg-slate-900'><div className='font-medium'>{b.bulletin}</div><div>{b.location || '—'} · {b.category || '—'}</div><div>{t('Proveedor IA')}: {b.provider || t('Falta proveedor IA')} · {t('Modelo')}: {b.model || '—'}</div><div>{t('Frecuencia')}: {b.frequency || t('No configurado')} · {t('Hora')}: {b.time || t('No configurado')}</div><div>{t('Próxima ejecución')}: {formatDateTime(b.next_run)} · {t('Última ejecución')}: {formatDateTime(b.last_run)}</div></div>)}</div>)}
    </CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Mapa editorial')}</CardTitle></CardHeader><CardContent className='grid gap-2 md:grid-cols-2'>{mapOverview.locations.map((l:any) => <div key={l.location} className='rounded border p-2 text-sm'><div className='font-medium'>{l.location}</div><div>{t('Informativos activos')}: {l.active_bulletins}</div><div>{t('Tareas pendientes')}: {l.pending_tasks}</div><div>{t('Fallido')}: {l.failed}</div><div>{t('Falta proveedor IA')}: {l.missing_provider}</div></div>)}<Link className='underline md:col-span-2' href={mapOverview.mapRoute}>{t('Abrir mapa editorial')}</Link></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Cola de trabajo editorial')}</CardTitle></CardHeader><CardContent className='space-y-2'>{actionableQueue.map((r:any) => <div key={r.id} className='flex items-center justify-between rounded border p-2 text-sm'><div><div className='font-medium'>{r.bulletin || '—'}</div><div>{r.location || '—'} {r.category ? `· ${r.category}` : ''}</div><div>{[r.provider, r.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</div></div><div className='text-right'><Badge>{t(r.status)}</Badge><div>{formatDateTime(r.scheduled_for)}</div><div>{t(r.next_action)}</div></div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Zonas y temáticas cubiertas')}</CardTitle></CardHeader><CardContent><p className='mb-2 text-xs'>{t('Esta sección muestra qué ubicaciones y categorías tienen informativos activos, pausados o con configuración faltante.')}</p><div className='grid gap-2 md:grid-cols-2'>{coverageOverview.groups.map((g:any, i:number) => <div key={i} className='rounded border p-2 text-sm'><div className='font-medium'>{g.location} · {g.category}</div><div>{t('Informativos activos')}: {g.active}</div><div>{t('Apagados')}: {g.paused}</div><div>{t('Configuración incompleta')}: {g.missing_configuration}</div></div>)}</div></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Estado de proveedores IA')}</CardTitle></CardHeader><CardContent className='grid gap-2 md:grid-cols-2'>{aiProviderStatus.map((p:any)=><div key={p.id} className='rounded border p-2 text-sm'><div className='font-semibold'>{p.name} · {p.model || '—'}</div><div>{t('Activo')}: {t(p.is_active ? 'Sí' : 'No')} · {t('Grounding')}: {t(p.grounded ? 'Sí' : 'No')}</div><div>{t('Uso hoy')}: {p.usage_today} · {t(p.availability === 'rate_limited' ? 'Limitado por tasa' : 'Disponible')}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Últimas ejecuciones')}</CardTitle></CardHeader><CardContent className='space-y-2'>{latestExecutions.map((r:any)=><div key={r.id} className='rounded border p-2 text-sm'><div className='font-medium'>{r.bulletin}</div><div>{formatDateTime(r.scheduled_for)} · {t(r.status)}</div><div>{[r.provider,r.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</div><div>{t('Fuentes')}: {t(r.sources_status)} · {t('Producción')}: {r.script ? t(r.script.production_status || 'Producción pendiente') : t('Producción pendiente')}</div><div>{t('Siguiente acción')}: {t(r.next_action)}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Pipeline editorial')}</CardTitle></CardHeader><CardContent className='text-sm'>{['Prompt','IA','Guion','Fuentes','Revisión','Producción','Audio pendiente','Vídeo pendiente','Publicación'].map((s, i)=><span key={s} className='mr-2'>{t(s)}{i<8?' → ':''}</span>)}</CardContent></Card>
  </EditorLayout>;
}
