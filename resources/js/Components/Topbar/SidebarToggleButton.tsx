import { useTranslations } from '@/i18n/useTranslations';
import { Menu, PanelLeftClose, PanelLeftOpen } from 'lucide-react';
import TopbarIconButton from './TopbarIconButton';

interface SidebarToggleButtonProps {
    collapsed: boolean;
    onDesktopToggle: () => void;
    onMobileOpen: () => void;
}

export default function SidebarToggleButton({ collapsed, onDesktopToggle, onMobileOpen }: SidebarToggleButtonProps): JSX.Element {
    const { t } = useTranslations();

    return (
        <>
            <TopbarIconButton
                className="md:hidden"
                icon={<Menu className="h-5 w-5" />}
                label={t('topbar.openMenu')}
                onClick={onMobileOpen}
            />
            <TopbarIconButton
                className="hidden md:inline-flex"
                icon={collapsed ? <PanelLeftOpen className="h-5 w-5" /> : <PanelLeftClose className="h-5 w-5" />}
                label={collapsed ? t('topbar.expandSidebar') : t('topbar.collapseSidebar')}
                onClick={onDesktopToggle}
            />
        </>
    );
}
