import { Button } from '@/Components/ui/button';
import { useTranslations } from '@/i18n/useTranslations';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export interface DashboardAction {
    key: string;
    label: string;
    href?: string | null;
    method?: 'get' | 'post';
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive';
}

export default function ActionButtonGroup({ actions, align = 'end' }: { actions: DashboardAction[]; align?: 'start' | 'end' }): JSX.Element | null {
    const { t } = useTranslations();
    const [open, setOpen] = useState(false);
    const menuRef = useRef<HTMLDivElement>(null);
    const visibleActions = actions.filter((action) => action.href && action.href !== '#');
    const primary = visibleActions.find((action) => action.variant === 'default' || action.variant === 'destructive') ?? visibleActions[0];
    const secondaryActions = visibleActions.filter((action) => action.key !== primary?.key);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: MouseEvent): void => {
            if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    if (!primary) {
        return null;
    }

    return (
        <div className={cn('flex items-center gap-2', align === 'end' ? 'justify-end' : 'justify-start')}>
            <ActionButton action={primary} />

            {secondaryActions.length > 0 && (
                <div ref={menuRef} className="relative">
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="h-9 w-9 px-0"
                        aria-label={t('editorActions.more')}
                        title={t('editorActions.more')}
                        onClick={() => setOpen((value) => !value)}
                    >
                        <MoreHorizontal className="h-4 w-4" />
                    </Button>

                    {open && (
                        <div
                            className={cn(
                                'absolute top-10 z-[70] w-56 rounded-xl border border-slate-200 bg-white p-1.5 text-sm shadow-xl ring-1 ring-slate-900/5 dark:border-slate-800 dark:bg-slate-950 dark:ring-white/10',
                                align === 'end' ? 'right-0' : 'left-0',
                            )}
                        >
                            <p className="px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                {t('editorActions.secondary')}
                            </p>
                            {secondaryActions.map((action) => (
                                <Link
                                    key={action.key}
                                    href={action.href as string}
                                    method={action.method === 'post' ? 'post' : undefined}
                                    as={action.method === 'post' ? 'button' : undefined}
                                    className="flex w-full items-center rounded-lg px-3 py-2 text-left font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white"
                                    onClick={() => setOpen(false)}
                                >
                                    {action.label}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

function ActionButton({ action }: { action: DashboardAction }): JSX.Element {
    return (
        <Button asChild size="sm" variant={action.variant ?? 'outline'} className="h-9 whitespace-nowrap">
            <Link href={action.href as string} method={action.method === 'post' ? 'post' : undefined} as={action.method === 'post' ? 'button' : undefined}>
                {action.label}
            </Link>
        </Button>
    );
}
