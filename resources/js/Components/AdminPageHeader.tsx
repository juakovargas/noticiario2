import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import PageHelp from '@/Components/Help/PageHelp';
import { useTranslations } from '@/i18n/useTranslations';
import { getPageHelp } from '@/help/pageHelp';

interface AdminPageHeaderProps {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
    helpKey?: string;
}

export default function AdminPageHeader({
    title,
    description,
    actionLabel,
    actionHref,
    helpKey,
}: AdminPageHeaderProps): JSX.Element {
    const { t } = useTranslations();
    const help = getPageHelp(t, helpKey);

    return (
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100">{title}</h1>
                <p className="text-sm text-slate-600 dark:text-slate-300">{description}</p>
            </div>

            <div className="flex items-center gap-2">
                <PageHelp help={help} />
                {actionLabel && actionHref && (
                    <Button asChild>
                        <Link href={actionHref}>{actionLabel}</Link>
                    </Button>
                )}
            </div>
        </div>
    );
}
