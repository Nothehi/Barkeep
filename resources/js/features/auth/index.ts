/**
 * The Identity module's client surface.
 *
 * Pages under `resources/js/pages/auth` are thin wrappers over these
 * components; Inertia requires page components to live under `pages/`, so the
 * reusable parts live here instead.
 */

export { default as ForgotPasswordForm } from './components/forgot-password-form';
export { default as LoginForm } from './components/login-form';
export { default as RegisterForm } from './components/register-form';
export { default as ResetPasswordForm } from './components/reset-password-form';
export { default as VerifyEmailForm } from './components/verify-email-form';
export { useAuth } from './hooks/use-auth';
export type { UseAuthResult } from './hooks/use-auth';
export type { Auth, AuthStatus, User, UserStatus } from './types/auth';
