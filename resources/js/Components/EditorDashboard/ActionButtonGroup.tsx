import { Button } from '@/Components/ui/button';
import { Link } from '@inertiajs/react';

export interface DashboardAction {
    key: string;
    label: string;
    href?: string | null;
    method?: 'get' | 'post';
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'destructive';
}

export default function ActionButtonGroup({ actions }: { actions: DashboardAction[] }): JSX.Element {
    return (
        <div className="flex flex-wrap gap-2">
            {actions.filter((action) => action.href && action.href !== '#').map((action) => (
                <Button key={action.key} asChild size="sm" variant={action.variant ?? 'outline'}>
                    <Link href={action.href as string} method={action.method === 'post' ? 'post' : undefined} as={action.method === 'post' ? 'button' : undefined}>
                        {action.label}
                    </Link>
                </Button>
            ))}
        </div>
    );
}
