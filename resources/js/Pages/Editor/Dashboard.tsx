import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

type Item = Record<string, any>;

type Props = { dailySummary: Record<string, number>; pendingTasks: Item[]; coverageMatrix: any; bulletinGroups: Record<string, Item[]>; editorialAlerts: Item[]; latestRuns: Item[]; aiProviders: Item[]; };

export default function Dashboard({ dailySummary, pendingTasks, coverageMatrix, bulletinGroups, editorialAlerts, latestRuns, aiProviders }: Props) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const cards = [
    ['Informativos activos', dailySummary.activeBulletins ?? 0],
    ['Tareas pendientes', dailySummary.pendingTasks ?? 0],
    ['Horarios vencidos', dailySummary.overdueSchedules ?? 0],
    ['Ejecuciones fallidas hoy', dailySummary.failedRunsToday ?? 0],
    ['Guiones pendientes de revisión', dailySummary.scriptsPendingReview ?? 0],
    ['Fuentes pendientes de verificación', dailySummary.sourcesPendingVerification ?? 0],
    ['Listos para producción', dailySummary.readyForProduction ?? 0],
  ];

  const reasonLabel = (reason?: string) => t(reason === 'missing_schedule' ? 'Sin horario' : reason === 'inactive_schedule' ? 'Horario inactivo' : reason === 'missing_provider' ? 'Sin proveedor de IA' : reason === 'missing_location_category' ? 'Sin ubicación o categoría' : reason === 'last_execution_failed' ? 'Última ejecución fallida' : reason === 'missing_next_run' ? 'Sin próxima ejecución' : 'Deshabilitado');

  return <EditorLayout><Head title={t('Panel de control editorial')} />
    <AdminPageHeader helpKey="editor.dashboard" title={t('Panel de control editorial')} description={t('Centro diario de operación editorial')} />
    <Card className='mb-4'><CardHeader><CardTitle>{t('Mapa editorial')}</CardTitle></CardHeader><CardContent><Link className='underline' href={route('editor.world-map.index')}>{t('Abrir mapa editorial')}</Link></CardContent></Card>
    <div className='grid gap-3 md:grid-cols-2 xl:grid-cols-4'>{cards.map(([k,v]) => <Card key={String(k)}><CardHeader><CardTitle className='text-sm'>{t(String(k))}</CardTitle></CardHeader><CardContent><div className='text-2xl font-semibold'>{v as number}</div></CardContent></Card>)}</div>

    <div className='mt-6 grid gap-4 lg:grid-cols-3'>
      <Card className='lg:col-span-2'><CardHeader><CardTitle>{t('Cola de trabajo editorial')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Hora')}</th><th>{t('Informativo')}</th><th>{t('Ubicación')}</th><th>{t('Categoría')}</th><th>{t('Proveedor IA')}</th><th>{t('Modelo')}</th><th>{t('Automatización')}</th><th>{t('Estado')}</th><th>{t('Siguiente acción')}</th></tr></thead><tbody>{pendingTasks.map((r)=><tr key={r.id} className='border-t'><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin_id?<Link className='underline' href={route('editor.bulletin-types.show', r.bulletin_id)}>{r.bulletin}</Link>:r.bulletin}</td><td>{r.location||'-'}</td><td>{r.category||'-'}</td><td>{r.provider||'-'}</td><td>{r.model||'-'}</td><td>{t(r.automation==='automatic'?'Automática':'Manual')}</td><td>{t(r.status==='overdue'?'Vencido':r.status==='disabled'?'Deshabilitado':r.status==='incomplete'?'Configuración incompleta':'Pendiente')}</td><td>{r.next_action==='run_now'?<Link className='underline' href={route('editor.automation.index')}>{t('Ejecutar ahora')}</Link>:<Link className='underline' href={route('editor.editorial-schedules.edit', r.schedule_id)}>{t(r.next_action==='configure'?'Configurar':'Editar horario')}</Link>}</td></tr>)}</tbody></table></CardContent></Card>
      <Card><CardHeader><CardTitle>{t('Estado de proveedores IA')}</CardTitle></CardHeader><CardContent className='space-y-2'>{aiProviders.map((p)=><div key={p.id} className='border rounded p-2 text-xs'><div className='font-medium'>{p.name} · {p.model || '-'}</div><div>{t(p.purpose === 'main_grounded_news' ? 'Proveedor principal para noticias actuales (Gemini Grounded).' : 'Proveedor auxiliar para reescritura y estilo (Groq).')}</div><div>{t(p.is_active ? 'Activo' : 'Inactivo')} · {t(p.grounded ? 'Con grounding' : 'Sin grounding')} · {t(p.availability === 'rate_limited' ? 'Limitado por tasa' : 'Disponible')} · {t('Uso hoy')}: {p.usage_today ?? 0}</div></div>)}</CardContent></Card>
    </div>

    <div className='mt-6 grid gap-4 lg:grid-cols-2'>
      {['active','paused','incomplete','attention'].map((g)=><Card key={g}><CardHeader><CardTitle>{t(g==='active'?'Informativos activos':g==='paused'?'Informativos pausados':g==='incomplete'?'Informativos incompletos':'Informativos con incidencias')}</CardTitle></CardHeader><CardContent className='space-y-2'>{(bulletinGroups[g]||[]).map((b)=><div key={b.id} className='border rounded p-2'><div className='font-medium'><Link className='underline' href={route('editor.bulletin-types.show', b.id)}>{b.name}</Link></div><div className='text-xs text-slate-500'>{[b.location,b.category,b.provider].filter(Boolean).join(' · ') || '-'}</div>{b.reason ? <div className='text-xs'>{reasonLabel(b.reason)}</div> : null}</div>)}</CardContent></Card>)}
    </div>

    <Card className='mt-6'><CardHeader><CardTitle>{t('Cobertura editorial')}</CardTitle></CardHeader><CardContent><div className='mb-2 text-xs'>{t('Leyenda')}: {coverageMatrix.legend?.map((s:string)=>t(s==='active'?'Activo':s==='disabled'?'Deshabilitado':s==='no_schedule'?'Sin horario':s==='missing_provider'?'Sin proveedor IA':s==='failed'?'Fallido':'No configurado')).join(' · ')}</div><div className='overflow-x-auto'><table className='text-sm'><thead><tr><th>{t('Ubicación')}</th>{coverageMatrix.categories.map((c:string)=><th key={c}>{c}</th>)}</tr></thead><tbody>{coverageMatrix.locations.map((l:string)=><tr key={l} className='border-t'><td className='font-medium'>{l}</td>{coverageMatrix.categories.map((c:string)=>{const cell=coverageMatrix.cells.find((x:any)=>x.location===l&&x.category===c); return <td key={c}>{t(cell?.state==='active'?'Activo':cell?.state==='disabled'?'Deshabilitado':cell?.state==='missing_provider'?'Sin proveedor IA':cell?.state==='no_schedule'?'Sin horario':cell?.state==='failed'?'Fallido':'No configurado')}</td>;})}</tr>)}</tbody></table></div></CardContent></Card>

    <Card className='mt-6'><CardHeader><CardTitle>{t('Últimas ejecuciones')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Hora')}</th><th>{t('Informativo')}</th><th>{t('Estado')}</th><th>{t('Proveedor IA')}</th><th>{t('Fuentes')}</th><th>{t('Producción')}</th><th/></tr></thead><tbody>{latestRuns.map((r)=><tr key={r.id} className='border-t'><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin_id?<Link className='underline' href={route('editor.bulletin-types.show', r.bulletin_id)}>{r.bulletin}</Link>:r.bulletin}</td><td>{r.status}</td><td>{[r.provider,r.model].filter(Boolean).join(' · ') || '-'}</td><td>{r.sources_count ?? 0} ({t('pendientes')}: {r.sources_pending ?? 0})</td><td>{t(r.script?.production_status==='ready_for_production'?'Listo para producción':'Producción pendiente')} · {t('Audio pendiente')} · {t('Vídeo pendiente')}</td><td>{r.prompt_run_id?<Link className='underline' href={route('editor.bulletin-prompt-runs.show', r.prompt_run_id)}>{t('Ver ejecución')}</Link>:null}{r.script?<Link className='ml-2 underline' href={route('editor.scripts.show', r.script.id)}>{t('Ver guion')}</Link>:null}</td></tr>)}</tbody></table></CardContent></Card>

    <Card className='mt-6'><CardHeader><CardTitle>{t('Alertas editoriales')}</CardTitle></CardHeader><CardContent>{editorialAlerts.length? editorialAlerts.map((a,i)=><div key={i} className='text-sm'>{t(a.message==='gemini_configured'?'Gemini Grounded está configurado y disponible.':a.message==='schedule_overdue'?'Hay un horario vencido.':a.message==='missing_provider'?'Hay un informativo sin proveedor de IA.':'Una ejecución falló hoy.')}</div>) : <div className='text-sm text-slate-500'>{t('Sin alertas')}</div>}</CardContent></Card>
  </EditorLayout>;
}
