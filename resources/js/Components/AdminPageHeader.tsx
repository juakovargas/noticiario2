import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';

interface AdminPageHeaderProps {
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}

export default function AdminPageHeader({
    title,
    description,
    actionLabel,
    actionHref,
}: AdminPageHeaderProps): JSX.Element {
    return (
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 className="text-2xl font-bold text-slate-900">{title}</h1>
                <p className="text-sm text-slate-600">{description}</p>
            </div>

            {actionLabel && actionHref && (
                <Button asChild>
                    <Link href={actionHref}>{actionLabel}</Link>
                </Button>
            )}
        </div>
    );
}
