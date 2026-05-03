import { ReactNode } from 'react';

export default function OperationalTable({ children, minWidth = '980px' }: { children: ReactNode; minWidth?: string }): JSX.Element {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm" style={{ minWidth }}>
                {children}
            </table>
        </div>
    );
}
