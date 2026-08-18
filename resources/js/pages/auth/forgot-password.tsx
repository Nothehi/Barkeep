import { Head } from '@inertiajs/react';
import { ForgotPasswordForm } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';

type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Forgot password')} />

            <ForgotPasswordForm status={status} />
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a password reset link',
};
