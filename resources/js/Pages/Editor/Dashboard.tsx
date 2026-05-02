import AdminPageHeader from '@/Components/AdminPageHeader';
import WorldBulletinMap from '@/Components/Maps/WorldBulletinMap';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';
import { Activity, AlertTriangle, Bot, CheckCircle2, Clock3, MapPinned, PlayCircle, Table2 } from 'lucide-react';

const pipelineSteps = ['Prompt', 'AI', 'Script', 'Sources', 'Review', 'Production', 'Audio pending', 'Video pending', 'Publishing'];

export default function Dashboard({ headerActions, summaryCards, mapOverview, scheduledBulletins, actionableQueue, aiEngines, coverageByLocationTopic, latestExecutions }: any) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();

  return (
    <EditorLayout>
      <Head title={t('Mesa de operaciones editoriales')} />
      <AdminPageHeader
        helpKey="editor.dashboard"
        title={t('Mesa de operaciones editoriales')}
        description={t('Centro de control diario para programación, ejecución IA y estado de producción por informativo.')}
      />

      <div className="mb-6 flex flex-wrap items-center gap-2">
        {headerActions?.map((action: any) => (
          <Button key={action.key} asChild variant={action.key === 'createBulletin' ? 'default' : 'outline'} size="sm" className="rounded-xl">
            <Link href={action.href}>{t(action.label)}</Link>
          </Button>
        ))}
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {summaryCards.map((card: any) => (
          <Card key={card.key} className="rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800">
            <CardHeader className="pb-2">
              <CardTitle className="text-xs uppercase tracking-wide text-muted-foreground">{t(card.label)}</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-end justify-between">
                <p className="text-3xl font-bold tracking-tight">{card.value ?? 0}</p>
                <Activity className="h-4 w-4 text-muted-foreground" />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="mt-6 grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-2 rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800">
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="flex items-center gap-2"><MapPinned className="h-4 w-4" />{t('Mapa editorial operativo')}</CardTitle>
            <Button asChild variant="outline" size="sm" className="rounded-xl"><Link href={mapOverview.mapRoute}>{t('Ver mapa completo')}</Link></Button>
          </CardHeader>
          <CardContent>
            <WorldBulletinMap panel="editor" markers={mapOverview.markers || []} />
          </CardContent>
        </Card>

        <Card className="rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800">
          <CardHeader><CardTitle className="flex items-center gap-2"><AlertTriangle className="h-4 w-4" />{t('Tareas que requieren acción')}</CardTitle></CardHeader>
          <CardContent className="space-y-3">
            {actionableQueue.length === 0 && <p className="text-sm text-muted-foreground">{t('Sin alertas')}</p>}
            {actionableQueue.slice(0, 8).map((item: any) => (
              <div key={item.id} className="rounded-xl border border-slate-200/80 bg-white p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                <div className="mb-1 flex items-center justify-between gap-2"><p className="font-semibold">{t(item.type_label)}</p><Badge variant="outline">{t(item.status)}</Badge></div>
                <p className="font-medium">{item.bulletin || '—'}</p>
                <p className="text-xs text-muted-foreground">{[item.provider, item.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</p>
                <div className="mt-2 flex gap-2">
                  {item.view_url && <Button asChild size="sm" variant="outline" className="rounded-lg"><Link href={item.view_url}>{t('Ver')}</Link></Button>}
                  {item.action_url && <Button asChild size="sm" className="rounded-lg"><Link href={item.action_url} method="post" as="button">{t(item.next_action)}</Link></Button>}
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6 rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800">
        <CardHeader>
          <CardTitle>{t('Cobertura por zona y temática')}</CardTitle>
          <p className="text-sm text-muted-foreground">{t(coverageByLocationTopic.explanation)}</p>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          {coverageByLocationTopic.groups.map((g: any, i: number) => (
            <div key={i} className="rounded-xl border border-slate-200/80 p-3 text-sm dark:border-slate-800">
              <div className="mb-2 font-semibold">{g.location} · {g.category}</div>
              <div className="flex flex-wrap gap-1">
                <Badge variant="success">{t('Activos')}: {g.active}</Badge><Badge variant="outline">{t('Pausados')}: {g.paused}</Badge><Badge variant="danger">{t('Sin proveedor IA')}: {g.missing_provider}</Badge><Badge variant="outline">{t('Sin horario')}: {g.missing_schedule}</Badge><Badge variant="danger">{t('Fallido')}: {g.failed}</Badge><Badge variant="outline">{t('No configurado')}: {g.not_configured}</Badge>
              </div>
            </div>
          ))}
        </CardContent>
      </Card>

      <Card className="mt-6 rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800">
        <CardHeader><CardTitle className="flex items-center gap-2"><Table2 className="h-4 w-4" />{t('Informativos programados')}</CardTitle></CardHeader>
        <CardContent className="overflow-x-auto">
          <table className="w-full min-w-[1100px] text-sm">
            <thead><tr className="border-b text-left text-xs uppercase tracking-wide text-muted-foreground"><th className="py-2">{t('ON/OFF')}</th><th>{t('Informativo')}</th><th>{t('Ubicación')}</th><th>{t('Categoría')}</th><th>{t('Proveedor IA')}</th><th>{t('Próxima ejecución')}</th><th>{t('Última ejecución')}</th><th>{t('Pipeline')}</th><th>{t('Acciones')}</th></tr></thead>
            <tbody>{scheduledBulletins.map((b: any) => <tr key={b.id} className="border-b align-top"><td className="py-3"><Badge variant={b.is_on ? 'success' : 'outline'}>{b.is_on ? 'ON' : 'OFF'}</Badge></td><td className="py-3 font-medium"><Link className="hover:underline" href={b.bulletin_url}>{b.bulletin}</Link></td><td className="py-3">{b.location || '—'}</td><td className="py-3">{b.category || '—'}</td><td className="py-3">{b.provider ? `${b.provider} · ${b.model || '—'}` : <Badge variant="danger">{t('Falta proveedor IA')}</Badge>}</td><td className="py-3">{formatDateTime(b.next_run)}</td><td className="py-3">{formatDateTime(b.last_run)} · {b.last_result ? t(b.last_result) : '—'}</td><td className="py-3"><div className="flex flex-wrap gap-1">{b.pipeline.map((step: string) => <span key={step} className="rounded-full border px-2 py-0.5 text-xs">{t(step)}</span>)}</div></td><td className="py-3"><div className="flex flex-wrap gap-2"><Button asChild size="sm" variant="outline"><Link href={b.view_url}>{t('Ver informativo')}</Link></Button><Button asChild size="sm" variant="outline"><Link href={b.runs_url}>{t('Ver ejecuciones')}</Link></Button><Button asChild size="sm"><Link href={b.run_now_url} method="post" as="button">{t('Ejecutar ahora')}</Link></Button></div></td></tr>)}</tbody>
          </table>
        </CardContent>
      </Card>

      <div className="mt-6 grid gap-4 xl:grid-cols-2">
        <Card className="rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800"><CardHeader><CardTitle className="flex items-center gap-2"><Bot className="h-4 w-4" />{t('Motores IA editoriales')}</CardTitle></CardHeader><CardContent className="space-y-3">{aiEngines.map((p: any) => <div key={p.id} className="rounded-xl border p-3 text-sm"><div className="flex flex-wrap items-center justify-between gap-2"><p className="font-semibold">{p.name} · {p.model || '—'}</p><Badge variant={p.availability === 'available' ? 'success' : 'danger'}>{t(p.availability_label)}</Badge></div><p className="text-muted-foreground">{t(p.purpose)}</p><p>{t('Con grounding')}: {t(p.grounded ? 'Sí' : 'No')}</p><p>{t('Informativos usando este motor')}: {p.bulletins.join(', ') || '—'}</p></div>)}</CardContent></Card>

        <Card className="rounded-2xl border-slate-200/80 shadow-sm dark:border-slate-800"><CardHeader><CardTitle className="flex items-center gap-2"><PlayCircle className="h-4 w-4" />{t('Últimas ejecuciones')}</CardTitle></CardHeader><CardContent className="space-y-3">{latestExecutions.map((r: any) => <div key={r.id} className="rounded-xl border p-3 text-sm"><div className="flex items-center justify-between gap-2"><p className="font-semibold">{r.bulletin || '—'}</p><Badge variant="outline">{t(r.status)}</Badge></div><p>{formatDateTime(r.scheduled_for)} · {[r.provider, r.model].filter(Boolean).join(' · ') || t('Falta proveedor IA')}</p><p>{t('Fuentes')}: {r.sources_label}</p><div className="mt-2 flex flex-wrap items-center gap-2">{pipelineSteps.map((step, idx) => <span key={`${r.id}-${step}`} className={`rounded-full border px-2 py-0.5 text-xs ${idx < r.pipeline.length ? 'border-emerald-300 text-emerald-700 dark:border-emerald-700 dark:text-emerald-300' : 'text-muted-foreground'}`}>{t(step)}</span>)}</div><div className="mt-2 flex flex-wrap gap-2"><Button asChild size="sm" variant="outline"><Link href={r.run_url}>{t('Ver ejecución')}</Link></Button>{r.script_url && <Button asChild size="sm" variant="outline"><Link href={r.script_url}>{t('Ver guion')}</Link></Button>}</div></div>)}</CardContent></Card>
      </div>
    </EditorLayout>
  );
}
