import { useMemo, useState } from 'react';

type AvatarUser = {
    name?: string | null;
    avatar_url?: string | null;
    initials?: string | null;
};

interface UserAvatarProps {
    user?: AvatarUser | null;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
}

const sizeClass: Record<NonNullable<UserAvatarProps['size']>, string> = {
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-16 w-16 text-lg',
    xl: 'h-24 w-24 text-2xl',
};

export default function UserAvatar({ user, size = 'sm', className = '' }: UserAvatarProps): JSX.Element {
    const [imageFailed, setImageFailed] = useState(false);

    const fallback = useMemo(() => {
        if (user?.initials) {
            return user.initials.toUpperCase();
        }

        const nameParts = (user?.name ?? '')
            .trim()
            .split(/\s+/u)
            .filter(Boolean)
            .slice(0, 2);

        return (nameParts.map((part) => part[0]).join('').slice(0, 2) || 'U').toUpperCase();
    }, [user?.initials, user?.name]);

    if (user?.avatar_url && !imageFailed) {
        return (
            <img
                src={user.avatar_url}
                alt={user?.name ? `${user.name} avatar` : 'Avatar'}
                className={`${sizeClass[size]} rounded-full object-cover ${className}`}
                onError={() => setImageFailed(true)}
            />
        );
    }

    return (
        <span
            aria-label="Avatar"
            className={`${sizeClass[size]} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 ${className}`}
        >
            {fallback}
        </span>
    );
}
