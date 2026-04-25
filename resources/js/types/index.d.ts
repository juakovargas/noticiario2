export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_active?: boolean;
    roles?: string[];
    permissions?: string[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
    };
};
