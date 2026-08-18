import { Head } from '@inertiajs/react';
import { VerifyEmailForm } from '@/features/auth';
import { useTranslation } from '@/lib/i18n';

type Props = {
    status?: string;
};

export default function VerifyEmail({ status }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('Email verification')} />

            <VerifyEmailForm status={status} />
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
