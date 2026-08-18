import { Head } from '@inertiajs/react';
import { ResetPasswordForm } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';

type Props = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function ResetPassword({ token, email, passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Reset password')} />

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
