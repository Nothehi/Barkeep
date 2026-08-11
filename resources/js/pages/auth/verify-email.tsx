import { Head } from '@inertiajs/react';
import { VerifyEmailForm } from '@/features/auth';

type Props = {
    status?: string;
};

export default function VerifyEmail({ status }: Props) {
    return (
        <>
            <Head title="Email verification" />

            <VerifyEmailForm status={status} />
        </>
    );
}

VerifyEmail.layout = {
    title: 'Email verification',
    description:
        'Please verify your email address by clicking on the link we just emailed to you.',
};
