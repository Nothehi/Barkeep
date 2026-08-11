/**
 * Identity owns the account shape, so it lives with the auth feature and is
 * re-exported here for the rest of the application.
 */
export type {
    Auth,
    AuthStatus,
    User,
    UserStatus,
} from '@/features/auth/types/auth';

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
