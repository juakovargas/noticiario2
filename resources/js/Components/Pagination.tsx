import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
}

export default function Pagination({ links }: PaginationProps): JSX.Element | null {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="mt-6 flex flex-wrap gap-2">
            {links.map((link, index) => (
                <Link
                    key={`${link.label}-${index}`}
                    href={link.url ?? '#'}
                    preserveScroll
                    className={`rounded-md px-3 py-1.5 text-sm transition ${
                        link.active
                            ? 'bg-slate-900 text-white'
                            : link.url
                              ? 'bg-white text-slate-700 hover:bg-slate-100'
                              : 'cursor-not-allowed bg-slate-100 text-slate-400'
                    }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </div>
    );
}
