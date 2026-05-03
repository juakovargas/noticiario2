import { cn } from '@/lib/utils';

interface Step {
    key: string;
    label: string;
    complete: boolean;
}

export default function PipelineStepBar({ steps }: { steps: Step[] }): JSX.Element {
    return (
        <div className="flex min-w-[220px] items-center gap-1">
            {steps.map((step, index) => (
                <div key={step.key} className="group flex flex-1 items-center gap-1" title={step.label}>
                    <span
                        className={cn(
                            'h-2 min-w-6 flex-1 rounded-full transition',
                            step.complete ? 'bg-emerald-500 dark:bg-emerald-400' : 'bg-slate-200 dark:bg-slate-800',
                        )}
                    />
                    {index < steps.length - 1 && <span className="sr-only">/</span>}
                </div>
            ))}
        </div>
    );
}
