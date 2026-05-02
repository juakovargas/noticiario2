import { useEffect, useMemo, useState } from 'react';

type AvatarUser = {
    name?: string | null;
    avatar_url?: string | null;
    avatarUrl?: string | null;
    initials?: string | null;
    profile_image_id?: number | string | null;
};

interface UserAvatarProps {
    user?: AvatarUser | null;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    className?: string;
}

const sizeClass: Record<NonNullable<UserAvatarProps['size']>, string> = {
    xs: 'h-6 w-6 text-[10px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-16 w-16 text-lg',
    xl: 'h-24 w-24 text-2xl',
};

function getAvatarSrc(user?: AvatarUser | null): string | null {
    const rawSrc = user?.avatar_url || user?.avatarUrl || null;

    if (!rawSrc || rawSrc.trim() === '') {
        return null;
    }

    if (/^https?:\/\//iu.test(rawSrc)) {
        return rawSrc;
    }

    return rawSrc.startsWith('/') ? rawSrc : `/${rawSrc}`;
}

export default function UserAvatar({ user, size = 'sm', className = '' }: UserAvatarProps): JSX.Element {
    const avatarSrc = getAvatarSrc(user);
    const cacheKey = user?.profile_image_id ?? 'no-image';
    const [imageFailed, setImageFailed] = useState(false);

    useEffect(() => {
        setImageFailed(false);
    }, [avatarSrc, cacheKey]);

    const fallback = useMemo(() => {
        if (user?.initials && user.initials.trim() !== '') {
            return user.initials.toUpperCase();
        }

        const nameParts = (user?.name ?? '')
            .trim()
            .split(/\s+/u)
            .filter(Boolean)
            .slice(0, 2);

        return (nameParts.map((part) => part[0]).join('').slice(0, 2) || 'U').toUpperCase();
    }, [user?.initials, user?.name]);

    if (avatarSrc && !imageFailed) {
        return (
            <img
                key={`${avatarSrc}-${cacheKey}`}
                src={avatarSrc}
                alt={`${user?.name ?? 'User'} avatar`}
                className={`${sizeClass[size]} rounded-full object-cover ${className}`}
                onError={() => {
                    setImageFailed(true);
                }}
            />
        );
    }

    return (
        <span
            aria-label="Avatar"
            title={avatarSrc ?? fallback}
            className={`${sizeClass[size]} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 ${className}`}
        >
            {fallback}
        </span>
    );
}