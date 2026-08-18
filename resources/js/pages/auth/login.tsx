import { Head } from '@inertiajs/react';
import { LoginForm } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Log in')} />

            <LoginForm status={status} canResetPassword={canResetPassword} />
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
