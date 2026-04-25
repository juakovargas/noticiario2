import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function FlashMessage(): JSX.Element | null {
    const { flash } = usePage<PageProps>().props;

    if (flash.success) {
        return (
            <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {flash.success}
            </div>
        );
    }

    if (flash.error) {
        return (
            <div className="rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {flash.error}
            </div>
        );
    }

    return null;
}
