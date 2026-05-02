import AdminPageHeader from '@/Components/AdminPageHeader';
import WorldBulletinMap from '@/Components/Maps/WorldBulletinMap';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ headerActions, summaryCards, mapOverview, scheduledBulletins, actionableQueue, aiEngines, coverageByLocationTopic, latestExecutions }: any) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();

  return <EditorLayout><Head title={t('Mesa de operaciones editoriales')} />
    <AdminPageHeader helpKey="editor.dashboard" title={t('Mesa de operaciones editoriales')} description={t('Supervisa programación, ejecución IA y estado de producción de cada informativo.')} />
    <div className='mb-4 flex flex-wrap gap-2'>
      {headerActions?.map((action: any) => <Button key={action.key} asChild variant='outline' size='sm'><Link href={action.href}>{t(action.label)}</Link></Button>)}
    </div>

    <div className='grid gap-3 md:grid-cols-2 xl:grid-cols-4'>
      {summaryCards.map((card: any) => <Card key={card.key}><CardHeader className='pb-2'><CardTitle className='text-sm'>{t(card.label)}</CardTitle></CardHeader><CardContent><div className='text-2xl font-semibold'>{card.value ?? 0}</div></CardContent></Card>)}
    </div>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Mapa editorial operativo')}</CardTitle></CardHeader><CardContent><WorldBulletinMap panel='editor' markers={mapOverview.markers || []} /><div className='mt-3'><Link className='text-sm underline' href={mapOverview.mapRoute}>{t('Ver mapa completo')}</Link></div></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Cobertura por zona y temática')}</CardTitle></CardHeader><CardContent className='grid gap-2 md:grid-cols-2'>{coverageByLocationTopic.groups.map((g:any, i:number)=><div key={i} className='rounded border p-3 text-sm'><div className='font-semibold'>{g.location} · {g.category}</div><div>{t('Activos')}: {g.active}</div><div>{t('Pausados')}: {g.paused}</div><div>{t('Falta proveedor IA')}: {g.missing_provider}</div><div>{t('Sin horario')}: {g.missing_schedule}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Informativos programados')}</CardTitle></CardHeader><CardContent className='overflow-x-auto'><table className='w-full text-sm'><thead><tr className='border-b text-left'><th>{t('ON/OFF')}</th><th>{t('Informativo')}</th><th>{t('Ubicación')}</th><th>{t('Categoría')}</th><th>{t('Proveedor IA')}</th><th>{t('Próxima ejecución')}</th><th>{t('Última ejecución')}</th><th>{t('Pipeline')}</th><th>{t('Acciones')}</th></tr></thead><tbody>{scheduledBulletins.map((b:any)=><tr key={b.id} className='border-b align-top'><td className='py-2'><Badge variant={b.is_on ? 'success':'outline'}>{b.is_on ? 'ON':'OFF'}</Badge></td><td className='py-2'><Link className='underline' href={b.bulletin_url}>{b.bulletin}</Link></td><td>{b.location || '—'}</td><td>{b.category || '—'}</td><td>{b.provider ? `${b.provider} · ${b.model || '—'}` : t('Falta proveedor IA')}</td><td>{formatDateTime(b.next_run)}</td><td>{formatDateTime(b.last_run)} · {b.last_result ? t(b.last_result) : '—'}</td><td>{b.pipeline.join(' → ')}</td><td className='space-x-2'><Link className='underline' href={b.view_url}>{t('Ver informativo')}</Link><Link className='underline' href={b.runs_url}>{t('Ver ejecuciones')}</Link></td></tr>)}</tbody></table></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Tareas por ejecutar')}</CardTitle></CardHeader><CardContent className='space-y-2'>{actionableQueue.map((item:any)=><div key={item.id} className='rounded border p-3 text-sm'><div className='font-semibold'>{t(item.type_label)} · {item.bulletin || '—'}</div><div>{[item.provider, item.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</div><div>{t('Estado')}: {t(item.status)} · {t('Siguiente acción')}: {t(item.next_action)}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Motores IA editoriales')}</CardTitle></CardHeader><CardContent className='grid gap-2 md:grid-cols-2'>{aiEngines.map((p:any)=><div key={p.id} className='rounded border p-3 text-sm'><div className='font-semibold'>{p.name} · {p.model || '—'}</div><div>{t('Grounding')}: {t(p.grounded ? 'Sí':'No')} · {t('Estado')}: {t(p.status)}</div><div>{t('Informativos usando este motor')}: {p.bulletins.join(', ') || '—'}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Últimas ejecuciones')}</CardTitle></CardHeader><CardContent className='space-y-2'>{latestExecutions.map((r:any)=><div key={r.id} className='rounded border p-3 text-sm'><div className='font-semibold'>{r.bulletin}</div><div>{formatDateTime(r.scheduled_for)} · {t(r.status)} · {[r.provider,r.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</div><div>{t('Pipeline')}: {r.pipeline.join(' → ')}</div></div>)}</CardContent></Card>
  </EditorLayout>;
}
