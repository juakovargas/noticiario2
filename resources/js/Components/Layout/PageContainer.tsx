import { cn } from '@/lib/utils';
import { PropsWithChildren } from 'react';

interface PageContainerProps extends PropsWithChildren {
    className?: string;
    constrained?: boolean;
}

export default function PageContainer({ children, className, constrained = false }: PageContainerProps): JSX.Element {
    return (
        <div className={cn('w-full px-4 py-6 md:px-8 lg:px-10', constrained ? 'mx-auto max-w-4xl' : '', className)}>
            {children}
        </div>
    );
}
