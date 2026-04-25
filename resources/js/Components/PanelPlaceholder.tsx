import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

interface PanelPlaceholderProps {
    title: string;
    description: string;
    badgeLabel?: string;
}

export default function PanelPlaceholder({
    title,
    description,
    badgeLabel = 'Coming soon',
}: PanelPlaceholderProps): JSX.Element {
    return (
        <Card>
            <CardHeader className="space-y-3">
                <Badge variant="outline" className="w-fit">
                    {badgeLabel}
                </Badge>
                <CardTitle>{title}</CardTitle>
                <CardDescription className="text-sm text-slate-600">{description}</CardDescription>
            </CardHeader>
            <CardContent className="text-sm text-slate-500">
                Pending implementation for the next development phase.
            </CardContent>
        </Card>
    );
}
