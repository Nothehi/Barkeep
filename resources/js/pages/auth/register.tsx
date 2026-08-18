import { Head } from '@inertiajs/react';
import { RegisterForm } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Register')} />

            <RegisterForm passwordRules={passwordRules} />
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
