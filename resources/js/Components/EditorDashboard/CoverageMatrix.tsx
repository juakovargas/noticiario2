import StatusBadge from './StatusBadge';

interface Props {
    groups: any[];
    labels: {
        active: string;
        paused: string;
        missingProvider: string;
        missingSchedule: string;
        failed: string;
    };
}

export default function CoverageMatrix({ groups, labels }: Props): JSX.Element {
    return (
        <div className="grid gap-3 md:grid-cols-2">
            {groups.map((group: any, index: number) => (
                <div key={`${group.location}-${group.category}-${index}`} className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p className="font-semibold text-slate-950 dark:text-white">{group.location} · {group.category}</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        <StatusBadge tone="success">{labels.active}: {group.active}</StatusBadge>
                        <StatusBadge>{labels.paused}: {group.paused}</StatusBadge>
                        <StatusBadge tone={group.missing_provider ? 'danger' : 'neutral'}>{labels.missingProvider}: {group.missing_provider}</StatusBadge>
                        <StatusBadge tone={group.missing_schedule ? 'warning' : 'neutral'}>{labels.missingSchedule}: {group.missing_schedule}</StatusBadge>
                        <StatusBadge tone={group.failed ? 'danger' : 'neutral'}>{labels.failed}: {group.failed}</StatusBadge>
                    </div>
                </div>
            ))}
        </div>
    );
}
