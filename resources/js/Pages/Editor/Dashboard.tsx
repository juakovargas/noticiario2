import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ summary, mapOverview, aiProviderStatus, workQueue, bulletinGroups, coverage, editorialAlerts, latestExecutions }: any) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const cards = [
    ['Informativos activos', summary.activeBulletins], ['Tareas pendientes', summary.pendingTasks], ['Horarios vencidos', summary.overdueSchedules],
    ['Ejecuciones fallidas hoy', summary.failedRunsToday], ['Guiones pendientes de revisión', summary.scriptsPendingReview], ['Fuentes pendientes de verificación', summary.sourcesPendingVerification], ['Listo para producción', summary.readyForProduction],
  ];

  return <EditorLayout><Head title={t('Mesa de trabajo editorial')} />
    <AdminPageHeader helpKey="editor.dashboard" title={t('Mesa de trabajo editorial')} description={t('Centro diario de operaciones editoriales')} />
    <div className='grid gap-3 md:grid-cols-2 xl:grid-cols-4'>{cards.map(([k,v])=><Card key={String(k)}><CardHeader><CardTitle className='text-sm'>{t(String(k))}</CardTitle></CardHeader><CardContent className='text-2xl font-semibold'>{v ?? 0}</CardContent></Card>)}</div>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Mapa editorial')}</CardTitle></CardHeader><CardContent className='grid gap-2 text-sm md:grid-cols-5'><div>{t('Ubicaciones activas')}: {mapOverview.activeLocations}</div><div>{t('Ubicaciones inactivas')}: {mapOverview.inactiveLocations}</div><div>{t('Ubicaciones sin proveedor IA')}: {mapOverview.locationsMissingProvider}</div><div>{t('Ubicaciones con tareas pendientes')}: {mapOverview.locationsPendingTasks}</div><div>{t('Ubicaciones con incidencias')}: {mapOverview.locationsWithFailures}</div><Link className='underline md:col-span-5' href={mapOverview.mapRoute}>{t('Abrir mapa editorial')}</Link></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Tareas por ejecutar')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Estado')}</th><th>{t('Hora')}</th><th>{t('Informativo')}</th><th>{t('Ubicación')}</th><th>{t('Categoría')}</th><th>{t('Proveedor IA')}</th><th>{t('Modelo')}</th><th>{t('Grounding')}</th><th>{t('Automatización')}</th><th>{t('Siguiente acción')}</th></tr></thead><tbody>{workQueue.map((r:any)=><tr key={r.id} className='border-t'><td>{t(r.priority)}</td><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin_id ? <Link className='underline' href={route('editor.bulletin-types.show', r.bulletin_id)}>{r.bulletin}</Link> : r.bulletin}</td><td>{r.location || '—'}</td><td>{r.category || '—'}</td><td>{r.provider === 'missing_ai_provider' ? t('Falta proveedor IA') : r.provider}</td><td>{r.model || '—'}</td><td>{t(r.grounded ? 'Sí' : 'No')}</td><td>{t(r.automation === 'automatic' ? 'Automática' : 'Manual')}</td><td>{r.next_action === 'run_now' ? t('Ejecutar ahora') : r.next_action === 'retry' ? t('Reintentar') : t('Configurar')}</td></tr>)}</tbody></table></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Estado de proveedores IA')}</CardTitle></CardHeader><CardContent className='grid gap-2 md:grid-cols-2'>{aiProviderStatus.map((p:any)=><div key={p.id} className='rounded border p-2 text-sm'><div className='font-semibold'>{p.name} · {p.model || '—'}</div><div>{t('Activo')}: {t(p.is_active ? 'Sí' : 'No')} · {t('Grounding')}: {t(p.grounded ? 'Sí' : 'No')}</div><div>{t('Uso hoy')}: {p.usage_today} · {t(p.availability === 'rate_limited' ? 'Limitado por tasa' : 'Disponible')}</div><div>{t('Usado por')}: {p.bulletins.length ? p.bulletins.join(', ') : t('No hay informativos asignados')}</div></div>)}</CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Cobertura editorial')}</CardTitle></CardHeader><CardContent><p className='mb-2 text-xs'>{t('La cobertura editorial muestra por ubicación y categoría qué está cubierto y qué falta configurar.')}</p><table className='text-sm'><thead><tr><th>{t('Ubicación')}</th>{coverage.categories.map((c:string)=><th key={c}>{c}</th>)}</tr></thead><tbody>{coverage.locations.map((l:string)=><tr key={l} className='border-t'><td>{l}</td>{coverage.categories.map((c:string)=>{const cell=coverage.cells.find((x:any)=>x.location===l && x.category===c); return <td key={c}>{t(cell?.state)}</td>;})}</tr>)}</tbody></table></CardContent></Card>

    <Card className='mt-4'><CardHeader><CardTitle>{t('Últimas ejecuciones')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Hora')}</th><th>{t('Informativo')}</th><th>{t('Estado')}</th><th>{t('Proveedor IA')}</th><th>{t('Fuentes')}</th></tr></thead><tbody>{latestExecutions.map((r:any)=><tr key={r.id} className='border-t'><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin}</td><td>{t(r.status)}</td><td>{[r.provider,r.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</td><td>{r.sources_count} ({t('pendientes')}: {r.sources_pending})</td></tr>)}</tbody></table></CardContent></Card>
  </EditorLayout>;
}
