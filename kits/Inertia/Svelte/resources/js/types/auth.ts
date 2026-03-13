export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    /* @2fa */
    two_factor_enabled?: boolean;
    /* @end-2fa */
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @2fa */
export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
/* @end-2fa */
