import { Form } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { useAuth } from '../hooks/use-auth';

type VerifyEmailFormProps = {
    status?: string;
};

export default function VerifyEmailForm({ status }: VerifyEmailFormProps) {
    const { user } = useAuth();

    return (
        <>
            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            {user && (
                <p className="mb-4 text-center text-sm text-muted-foreground">
                    We sent the link to{' '}
                    <span className="font-medium text-foreground">
                        {user.email}
                    </span>
                    .
                </p>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            Resend verification email
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            Log out
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
