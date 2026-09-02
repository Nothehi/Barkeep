import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, login, register } from '@/routes';
import DesignArc from './design-arc';

/**
 * The first screen.
 *
 * Two columns on a wide viewport and one on a narrow one, with the picture
 * following the words rather than sitting beside them — the sentence is what
 * has to land first, and on a phone it is all there is room for.
 *
 * The decoration behind it is symmetric on purpose. A hero glow that has a
 * left and a right has to be mirrored for Persian, and one that does not need
 * mirroring is one that cannot be got wrong.
 */
export default function Hero() {
    const { auth } = usePage().props;
    const { t } = useTranslation();

    return (
        <section className="relative overflow-hidden">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-10"
            >
                <div
                    className="absolute inset-0 opacity-70"
                    style={{
                        backgroundImage:
                            'linear-gradient(to right, var(--border) 1px, transparent 1px), linear-gradient(to bottom, var(--border) 1px, transparent 1px)',
                        backgroundSize: '56px 56px',
                        WebkitMaskImage:
                            'radial-gradient(ellipse 70% 60% at 50% 0%, #000, transparent 75%)',
                        maskImage:
                            'radial-gradient(ellipse 70% 60% at 50% 0%, #000, transparent 75%)',
                    }}
                />

                <div className="absolute inset-x-0 -top-48 mx-auto size-[34rem] rounded-full bg-amber-500/15 blur-3xl dark:bg-amber-400/10" />
            </div>

            <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 py-20 lg:grid-cols-2 lg:gap-16 lg:py-28">
                <div>
                    <p className="inline-flex items-center gap-2 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-medium text-amber-700 dark:text-amber-300">
                        {t('Board-game design, made executable')}
                    </p>

                    <h1 className="mt-6 text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-[3.5rem] lg:leading-[1.05]">
                        {t(
                            'Turn a design method into a workflow you can actually run',
                        )}
                    </h1>

                    <p className="mt-6 max-w-xl text-lg leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'Advice about designing a board game stops at principles, practices and checklists. Here they carry on: into the questions your game has to answer, decisions that keep their reasoning, prototypes, playtests, and the version that comes out of them.',
                        )}
                    </p>

                    <div className="mt-8 flex flex-wrap items-center gap-3">
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

                    <p className="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
                        <Languages className="size-4 shrink-0" />
                        {t('English and Persian, right to left included.')}
                    </p>
                </div>

                <DesignArc />
            </div>
        </section>
    );
}
