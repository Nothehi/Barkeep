import { Head } from '@inertiajs/react';
import { ForgotPasswordForm } from '@/features/auth';

type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    return (
        <>
            <Head title="Forgot password" />

            <ForgotPasswordForm status={status} />
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a password reset link',
};
