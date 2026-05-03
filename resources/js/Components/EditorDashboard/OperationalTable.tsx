import { ReactNode } from 'react';

export default function OperationalTable({ children, minWidth = '980px' }: { children: ReactNode; minWidth?: string }): JSX.Element {
    const style = minWidth === '0' ? undefined : { minWidth };

    return (
        <div className="w-full overflow-visible">
            <table className="w-full table-auto text-sm" style={style}>
                {children}
            </table>
        </div>
    );
}
