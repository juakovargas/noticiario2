import AdminLayout from '@/Layouts/AdminLayout';
import EditorLayout from '@/Layouts/EditorLayout';
import ViewerLayout from '@/Layouts/ViewerLayout';
import { PropsWithChildren } from 'react';

interface ProfilePanelLayoutProps extends PropsWithChildren {
    panel: 'admin' | 'editor' | 'viewer';
}

export default function ProfilePanelLayout({ panel, children }: ProfilePanelLayoutProps): JSX.Element {
    if (panel === 'admin') {
        return <AdminLayout>{children}</AdminLayout>;
    }

    if (panel === 'viewer') {
        return <ViewerLayout>{children}</ViewerLayout>;
    }

    return <EditorLayout>{children}</EditorLayout>;
}
