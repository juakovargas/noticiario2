import UserAvatar from '@/Components/UserAvatar';

type IdentityUser = {
    name?: string | null;
    email?: string | null;
    avatar_url?: string | null;
    avatarUrl?: string | null;
    initials?: string | null;
};

interface UserIdentityProps {
    user?: IdentityUser | null;
    subtitle?: string | null;
    avatarSize?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
}

export default function UserIdentity({
    user,
    subtitle,
    avatarSize = 'sm',
    className = '',
}: UserIdentityProps): JSX.Element {
    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <UserAvatar user={user} size={avatarSize} />
            <div>
                <p className="text-sm font-medium text-slate-900">{user?.name ?? 'User'}</p>
                {subtitle ? <p className="text-xs text-slate-500">{subtitle}</p> : null}
            </div>
        </div>
    );
}
