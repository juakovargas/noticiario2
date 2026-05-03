import StatusBadge from './StatusBadge';

interface Provider {
    name?: string | null;
    model?: string | null;
    default_model?: string | null;
    supports_grounding?: boolean;
}

interface Props {
    provider?: Provider | null;
    missingLabel: string;
    groundedLabel?: string;
}

export default function ProviderBadge({ provider, missingLabel, groundedLabel }: Props): JSX.Element {
    if (!provider?.name) {
        return <StatusBadge tone="danger">{missingLabel}</StatusBadge>;
    }

    return (
        <div className="space-y-1">
            <div className="flex flex-wrap items-center gap-2">
                <span className="font-semibold text-slate-900 dark:text-slate-100">{provider.name}</span>
                {provider.supports_grounding && groundedLabel && <StatusBadge tone="success">{groundedLabel}</StatusBadge>}
            </div>
            <p className="text-xs text-slate-500 dark:text-slate-400">{provider.model || provider.default_model || '-'}</p>
        </div>
    );
}
