export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    roles?: string[];
    permissions?: string[];
}

export interface AvailableLocale {
    code: 'en' | 'es';
    label: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    i18n: {
        locale: 'en' | 'es';
        availableLocales: AvailableLocale[];
    };
    flash: {
        success?: string;
        error?: string;
    };
};
