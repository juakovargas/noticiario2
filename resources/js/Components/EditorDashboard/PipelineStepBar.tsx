import { cn } from '@/lib/utils';
import { CheckCircle2, Circle } from 'lucide-react';

interface Step {
    key: string;
    label: string;
    complete: boolean;
    tone?: 'neutral' | 'info' | 'success' | 'warning' | 'danger' | 'violet';
}

export default function PipelineStepBar({ steps }: { steps: Step[] }): JSX.Element {
    return (
        <div className="flex min-w-0 flex-wrap gap-1.5">
            {steps.map((step) => (
                <span
                    key={step.key}
                    className={cn(
                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold',
                        step.complete
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                            : toneClass(step.tone),
                    )}
                >
                    {step.complete ? <CheckCircle2 className="h-3.5 w-3.5" /> : <Circle className="h-3.5 w-3.5" />}
                    <span>{step.label}</span>
                </span>
            ))}
        </div>
    );
}

function toneClass(tone: Step['tone'] = 'neutral'): string {
    const tones: Record<NonNullable<Step['tone']>, string> = {
        neutral: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
        info: 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-200',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
        warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
        danger: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200',
        violet: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-200',
    };

    return tones[tone];
}
