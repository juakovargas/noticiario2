export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles?: string[];
    permissions?: string[];
    preferred_locale?: string | null;
    timezone?: string | null;
    date_format?: string | null;
    time_format?: string | null;
    appearance?: 'light' | 'dark' | 'system' | null;
    avatar_url?: string | null;
    avatarUrl?: string | null;
    initials?: string | null;
    profile_image_id?: number | null;
}

export interface AvailableLocale {
    code: string;
    name?: string;
    native_name?: string | null;
    flag_emoji?: string | null;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    impersonation: {
        active: boolean;
        impersonator_id: number | null;
        impersonator_name: string | null;
        current_user_name: string | null;
    };
    i18n: {
        locale: string;
        availableLocales: AvailableLocale[];
    };
    flash: {
        success?: string;
        error?: string;
    };
};
