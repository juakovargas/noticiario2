interface UserAvatarProps {
    name?: string | null;
    avatarUrl?: string | null;
    initials?: string | null;
    className?: string;
}

export default function UserAvatar({ name, avatarUrl, initials, className = 'h-8 w-8' }: UserAvatarProps): JSX.Element {
    const fallback = (initials || name?.split(' ').map((part) => part[0]).join('').slice(0, 2) || 'U').toUpperCase();

    if (avatarUrl) {
        return (
            <img
                src={avatarUrl}
                alt={name || 'User'}
                className={`${className} rounded-full object-cover`}
                onError={(event) => {
                    (event.currentTarget as HTMLImageElement).style.display = 'none';
                }}
            />
        );
    }

    return <span className={`${className} inline-flex items-center justify-center rounded-full bg-slate-200 text-xs font-semibold text-slate-700`}>{fallback}</span>;
}
