import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
}

function normalizeLabel(label: string): string {
    if (label.includes('&laquo;')) {
        return '« Previous';
    }

    if (label.includes('&raquo;')) {
        return 'Next »';
    }

    return label;
}

export default function Pagination({ links }: PaginationProps): JSX.Element | null {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-6 flex flex-wrap gap-2" aria-label="Pagination">
            {links.map((link, index) => {
                const label = normalizeLabel(link.label);

                return (
                    <Link
                        key={`${label}-${index}`}
                        href={link.url ?? '#'}
                        preserveScroll
                        className={`rounded-md px-3 py-1.5 text-sm transition ${
                            link.active
                                ? 'bg-slate-900 text-white'
                                : link.url
                                  ? 'bg-white text-slate-700 hover:bg-slate-100'
                                  : 'cursor-not-allowed bg-slate-100 text-slate-400'
                        }`}
                        aria-disabled={!link.url}
                    >
                        {label}
                    </Link>
                );
            })}
        </nav>
    );
}
