/**
 * The account representation Identity shares with every Inertia page.
 *
 * Mirrors `Modules\Identity\Presentation\Http\Resources\AuthenticatedUserResource`.
 * Keep the two in sync — the resource is the authoritative shape.
 */

export type UserStatus = 'active' | 'suspended' | 'disabled';

export type User = {
    id: string;
    name: string;
    email: string;
    status: UserStatus;
    email_verified_at: string | null;
    created_at: string | null;
    two_factor_enabled?: boolean;
    last_login_at?: string | null;
    updated_at?: string | null;
    avatar?: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

/**
 * There is no loading state: Inertia resolves the account server side before
 * the page renders, so a page is either authenticated or it is not.
 */
export type AuthStatus = 'authenticated' | 'unauthenticated';
