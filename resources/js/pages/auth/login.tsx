import { Head } from '@inertiajs/react';
import { LoginForm } from '@/features/auth';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Log in" />

            <LoginForm status={status} canResetPassword={canResetPassword} />
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
