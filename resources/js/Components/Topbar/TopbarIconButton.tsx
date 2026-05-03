import { cn } from '@/lib/utils';
import { ButtonHTMLAttributes, forwardRef, ReactNode } from 'react';

interface TopbarIconButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    icon: ReactNode;
    label: string;
    active?: boolean;
    badge?: string | number | null;
}

const TopbarIconButton = forwardRef<HTMLButtonElement, TopbarIconButtonProps>(
    ({ icon, label, active = false, badge = null, className, ...props }, ref) => {
        return (
            <button
                ref={ref}
                type="button"
                aria-label={label}
                title={label}
                className={cn(
                    'relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-700 dark:hover:bg-slate-800 dark:hover:text-white',
                    active && 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-200',
                    className,
                )}
                {...props}
            >
                {icon}
                {badge !== null && badge !== undefined && badge !== '' && (
                    <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-rose-500 px-1.5 py-0.5 text-center text-[10px] font-bold leading-none text-white ring-2 ring-white dark:ring-slate-900">
                        {badge}
                    </span>
                )}
            </button>
        );
    },
);

TopbarIconButton.displayName = 'TopbarIconButton';

export default TopbarIconButton;
