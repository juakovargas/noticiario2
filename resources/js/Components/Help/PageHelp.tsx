import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { X } from 'lucide-react';
import { useState } from 'react';

export interface PageHelpData {
    title: string;
    summary: string;
    sections: Array<{ title: string; content: string }>;
    tips?: string[];
    nextSteps?: string[];
}

export default function PageHelp({ help }: { help: PageHelpData | null }): JSX.Element | null {
    const { t } = useTranslations();
    const [open, setOpen] = useState(false);

    if (!help) return null;

    return (
        <>
            <Button type="button" variant="outline" size="sm" onClick={() => setOpen(true)}>{t('Help')}</Button>
            {open ? (
                <div className="fixed inset-0 z-50 bg-slate-950/40" role="dialog" aria-modal="true" onClick={() => setOpen(false)}>
                    <div className="absolute right-0 top-0 h-full w-full max-w-xl overflow-y-auto border-l border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900" onClick={(e) => e.stopPropagation()}>
                        <div className="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-slate-100">{help.title}</h2>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{help.summary}</p>
                            </div>
                            <button type="button" className="rounded p-1 text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" onClick={() => setOpen(false)} aria-label={t('Close help')}><X className="h-5 w-5" /></button>
                        </div>
                        <div className="space-y-4 text-sm">
                            {help.sections.map((section) => (
                                <div key={section.title}>
                                    <h3 className="font-medium text-slate-900 dark:text-slate-100">{section.title}</h3>
                                    <p className="text-slate-700 dark:text-slate-300">{section.content}</p>
                                </div>
                            ))}
                            {help.tips && help.tips.length > 0 ? <div><h3 className="font-medium text-slate-900 dark:text-slate-100">{t('Tips')}</h3><ul className="list-disc space-y-1 pl-5 text-slate-700 dark:text-slate-300">{help.tips.map((tip) => <li key={tip}>{tip}</li>)}</ul></div> : null}
                            {help.nextSteps && help.nextSteps.length > 0 ? <div><h3 className="font-medium text-slate-900 dark:text-slate-100">{t('Next steps')}</h3><ul className="list-disc space-y-1 pl-5 text-slate-700 dark:text-slate-300">{help.nextSteps.map((step) => <li key={step}>{step}</li>)}</ul></div> : null}
                        </div>
                    </div>
                </div>
            ) : null}
        </>
    );
}
