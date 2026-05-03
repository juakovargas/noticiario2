import { RefObject, useEffect } from 'react';

export function useCloseOnOutside<T extends HTMLElement>(
    ref: RefObject<T>,
    open: boolean,
    onClose: () => void,
): void {
    useEffect(() => {
        if (!open) {
            return;
        }

        const onPointerDown = (event: MouseEvent): void => {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                onClose();
            }
        };

        const onKeyDown = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [onClose, open, ref]);
}

export const dropdownPanelClass =
    'absolute right-0 top-12 z-[80] w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5 dark:border-slate-800 dark:bg-slate-900 dark:shadow-black/30 dark:ring-white/10';

export const dropdownItemClass =
    'flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white';
