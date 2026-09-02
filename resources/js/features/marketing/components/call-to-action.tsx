import { Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, login, register } from '@/routes';

/**
 * The last thing on the page, and the same offer the header makes.
 *
 * Somebody who has read this far has read the argument; what they need now is
 * the button, not another one.
 */
export default function CallToAction() {
    const { auth } = usePage().props;
    const { t } = useTranslation();

    return (
        <section className="py-20 sm:py-28">
            <div className="mx-auto w-full max-w-6xl px-6">
                <div className="relative overflow-hidden rounded-2xl border bg-card px-6 py-16 text-center">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-x-0 -top-40 -z-10 mx-auto size-[28rem] rounded-full bg-amber-500/15 blur-3xl dark:bg-amber-400/10"
                    />

                    <h2 className="mx-auto max-w-2xl text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        {t('Your next game deserves a process')}
                    </h2>

                    <p className="mx-auto mt-4 max-w-xl text-base leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'Start a workspace, name the thing you have been thinking about, and let the method carry some of the weight.',
                        )}
                    </p>

                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        {auth.user ? (
                            <Button size="lg" asChild>
                                <Link href={dashboard()}>
                                    {t('Dashboard')}
                                    <ArrowRight className="rtl:rotate-180" />
                                </Link>
                            </Button>
                        ) : (
                            <>
                                <Button size="lg" asChild>
                                    <Link href={register()}>
                                        {t('Start designing')}
                                        <ArrowRight className="rtl:rotate-180" />
                                    </Link>
                                </Button>

                                <Button size="lg" variant="outline" asChild>
                                    <Link href={login()}>{t('Log in')}</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
