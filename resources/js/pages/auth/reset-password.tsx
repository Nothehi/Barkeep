import { Head } from '@inertiajs/react';
import { ResetPasswordForm } from '@/features/auth';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    return (
        <>
            <Head title="Reset password" />

            <ResetPasswordForm
                token={token}
                email={email}
                passwordRules={passwordRules}
            />
        </>
    );
}

ResetPassword.layout = {
    title: 'Reset password',
    description: 'Please enter your new password below',
};
