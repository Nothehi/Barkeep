import { router, usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import { logout as logoutRoute } from '@/routes';
import type { Auth, AuthStatus, User } from '../types/auth';

export type UseAuthResult = {
    user: User | null;
    status: AuthStatus;
    isAuthenticated: boolean;
    isEmailVerified: boolean;
    logout: () => void;
};

/**
 * The single source of authentication state for the client.
 *
 * Reads the `auth` prop that the Identity module shares with every Inertia
 * response, so no page has to fetch or cache the current account itself.
 */
export function useAuth(): UseAuthResult {
    const page = usePage<{ auth?: Auth }>();
    const user = page.props.auth?.user ?? null;

    const logout = useCallback(() => {
        router.post(logoutRoute.url());
    }, []);

    return useMemo(
        () => ({
            user,
            status: user ? 'authenticated' : 'unauthenticated',
            isAuthenticated: user !== null,
            isEmailVerified: user?.email_verified_at != null,
            logout,
        }),
        [user, logout],
    );
}
