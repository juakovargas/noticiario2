import { useTranslations } from '@/i18n/useTranslations';
import { User } from '@/types';
import SidebarToggleButton from './SidebarToggleButton';
import TopbarAppearanceToggle from './TopbarAppearanceToggle';
import TopbarLanguageDropdown from './TopbarLanguageDropdown';
import TopbarMessagesButton from './TopbarMessagesButton';
import TopbarUserMenu from './TopbarUserMenu';

interface AppTopbarProps {
    panel: 'admin' | 'editor' | 'viewer';
    title: string;
    subtitle?: string;
    user?: User | null;
    sidebarCollapsed: boolean;
    onSidebarToggle: () => void;
    onMobileMenuOpen: () => void;
    canOpenMessages?: boolean;
    unreadMessagesCount?: number;
}

export default function AppTopbar({
    panel,
    title,
    subtitle,
    user,
    sidebarCollapsed,
    onSidebarToggle,
    onMobileMenuOpen,
    canOpenMessages = false,
    unreadMessagesCount = 0,
}: AppTopbarProps): JSX.Element {
    const { t } = useTranslations();

    return (
        <header className="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/80">
            <div className="flex h-16 items-center gap-3 px-4 md:px-6 lg:px-8">
                <SidebarToggleButton
                    collapsed={sidebarCollapsed}
                    onDesktopToggle={onSidebarToggle}
                    onMobileOpen={onMobileMenuOpen}
                />

                <div className="min-w-0">
                    <p className="truncate text-sm font-bold text-slate-950 dark:text-white md:text-base">{title}</p>
                    {subtitle && <p className="hidden truncate text-xs font-medium text-slate-500 dark:text-slate-400 sm:block">{subtitle}</p>}
                </div>

                <div className="ml-auto flex items-center gap-2">
                    <TopbarAppearanceToggle />
                    <TopbarMessagesButton canOpenMessages={canOpenMessages} unreadMessagesCount={unreadMessagesCount} />
                    <TopbarLanguageDropdown />
                    <TopbarUserMenu user={user} panel={panel} />
                </div>

                <span className="sr-only">{t('topbar.notifications')}</span>
            </div>
        </header>
    );
}
