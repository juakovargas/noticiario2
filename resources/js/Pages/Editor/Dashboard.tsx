import AdminPageHeader from '@/Components/AdminPageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { useTranslations } from '@/i18n/useTranslations';
import EditorLayout from '@/Layouts/EditorLayout';
import { useDateFormatter } from '@/lib/useDateFormatter';
import { Head, Link } from '@inertiajs/react';

type Item = Record<string, any>;

type Props = {
  dailySummary: Record<string, number>;
  pendingTasks: Item[];
  coverageMatrix: { locations: string[]; categories: string[]; cells: { location: string; category: string; state: string }[] };
  activeBulletins: Item[];
  inactiveBulletins: Item[];
  editorialAlerts: Item[];
  latestRuns: Item[];
};

export default function Dashboard({ dailySummary, pendingTasks, coverageMatrix, activeBulletins, inactiveBulletins, editorialAlerts, latestRuns }: Props) {
  const { t } = useTranslations();
  const { formatDateTime } = useDateFormatter();
  const cards = [
    ['Active bulletins', dailySummary.activeBulletins ?? 0],
    ['Pending tasks', dailySummary.pendingTasks ?? 0],
    ['Overdue schedules', dailySummary.overdueSchedules ?? 0],
    ['Failed runs today', dailySummary.failedRunsToday ?? 0],
    ['Scripts pending review', dailySummary.scriptsPendingReview ?? 0],
    ['Sources pending verification', dailySummary.sourcesPendingVerification ?? 0],
    ['Ready for production', dailySummary.readyForProduction ?? 0],
  ];

  return <EditorLayout><Head title={t('Editorial control panel')} />
    <AdminPageHeader helpKey="editor.dashboard" title={t('Editorial control panel')} description={t('Editorial workbench')} />
    <div className='grid gap-3 md:grid-cols-2 xl:grid-cols-4'>{cards.map(([k,v]) => <Card key={String(k)}><CardHeader><CardTitle className='text-sm'>{t(String(k))}</CardTitle></CardHeader><CardContent><div className='text-2xl font-semibold'>{v as number}</div></CardContent></Card>)}</div>

    <div className='mt-6 grid gap-4 lg:grid-cols-3'>
      <Card className='lg:col-span-2'><CardHeader><CardTitle>{t('Work queue')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Time')}</th><th>{t('Bulletin')}</th><th>{t('Location')}</th><th>{t('Category')}</th><th>{t('Provider')}</th><th>{t('Automation')}</th><th>{t('Status')}</th><th>{t('Next action')}</th></tr></thead><tbody>{pendingTasks.map((r)=><tr key={r.id} className='border-t'><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin}</td><td>{r.location||'-'}</td><td>{r.category||'-'}</td><td>{r.provider||'-'}</td><td>{t(r.automation==='automatic'?'Automatic':'Manual')}</td><td>{t(r.status==='overdue'?'Overdue':r.status==='disabled'?'Disabled':r.status==='incomplete'?'Incomplete configuration':'Pending')}</td><td>{r.next_action==='run_now'?<Link className='underline' href={route('editor.automation.index')}>{t('Run now')}</Link>:<Link className='underline' href={route('editor.editorial-schedules.edit', r.schedule_id)}>{t(r.next_action==='configure'?'Configure':'Edit schedule')}</Link>}</td></tr>)}</tbody></table></CardContent></Card>
      <div className='space-y-4'>
        <Card><CardHeader><CardTitle>{t('Active bulletins')}</CardTitle></CardHeader><CardContent className='space-y-2'>{activeBulletins.map((b)=><div key={b.id} className='border rounded p-2'><div className='font-medium'>{b.name}</div><div className='text-xs text-slate-500'>{[b.location,b.category,b.provider].filter(Boolean).join(' · ')}</div></div>)}</CardContent></Card>
        <Card><CardHeader><CardTitle>{t('Disabled bulletins')}</CardTitle></CardHeader><CardContent className='space-y-2'>{inactiveBulletins.map((b)=><div key={b.id} className='border rounded p-2'><div className='font-medium'>{b.name}</div><div className='text-xs text-slate-500'>{t(b.reason==='missing_provider'?'Missing provider':b.reason==='missing_schedule'?'Missing schedule':'Disabled')}</div></div>)}</CardContent></Card>
        <Card><CardHeader><CardTitle>{t('Editorial alerts')}</CardTitle></CardHeader><CardContent>{editorialAlerts.length? editorialAlerts.map((a,i)=><div key={i} className='text-sm'>{t(a.message==='gemini_configured'?'Gemini Grounded is configured and available.':a.message==='schedule_overdue'?'A schedule is overdue.':a.message==='missing_provider'?'A bulletin has no provider configured.':'A schedule failed today.')}</div>) : <div className='text-sm text-slate-500'>{t('No alerts')}</div>}</CardContent></Card>
      </div>
    </div>
    <Card className='mt-6'><CardHeader><CardTitle>{t('Editorial coverage')}</CardTitle></CardHeader><CardContent><div className='overflow-x-auto'><table className='text-sm'><thead><tr><th>{t('Location')}</th>{coverageMatrix.categories.map((c)=><th key={c}>{c}</th>)}</tr></thead><tbody>{coverageMatrix.locations.map((l)=><tr key={l} className='border-t'><td className='font-medium'>{l}</td>{coverageMatrix.categories.map((c)=>{const cell=coverageMatrix.cells.find((x)=>x.location===l&&x.category===c); return <td key={c}>{t(cell?.state==='active'?'Active':cell?.state==='disabled'?'Disabled':cell?.state==='missing_provider'?'Missing provider':cell?.state==='no_schedule'?'Missing schedule':'Not covered')}</td>;})}</tr>)}</tbody></table></div></CardContent></Card>
    <Card className='mt-6'><CardHeader><CardTitle>{t('Latest executions')}</CardTitle></CardHeader><CardContent><table className='w-full text-sm'><thead><tr><th>{t('Time')}</th><th>{t('Bulletin')}</th><th>{t('Status')}</th><th>{t('Provider')}</th><th/></tr></thead><tbody>{latestRuns.map((r)=><tr key={r.id} className='border-t'><td>{formatDateTime(r.scheduled_for)}</td><td>{r.bulletin}</td><td>{r.status}</td><td>{r.provider||'-'}</td><td>{r.prompt_run_id?<Link className='underline' href={route('editor.bulletin-prompt-runs.show', r.prompt_run_id)}>{t('View prompt run')}</Link>:null}{r.script?<Link className='ml-2 underline' href={route('editor.scripts.show', r.script.id)}>{t('View script')}</Link>:null}</td></tr>)}</tbody></table></CardContent></Card>
  </EditorLayout>;
}
