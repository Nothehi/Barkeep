import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Compass,
    Dices,
    ListChecks,
    RefreshCw,
} from 'lucide-react';
import { useFormatters, useTranslation } from '@/lib/i18n';
import SectionHeading from './section-heading';

type Step = {
    icon: LucideIcon;
    title: string;
    body: string;
};

/**
 * The loop the whole product is arranged around.
 *
 * Four steps rather than the nine boxes of the method's own diagram: a
 * visitor is deciding whether this is for them, not learning the framework.
 * The arrow between the cards is the one that has to flip for Persian, which
 * is why it carries `rtl:rotate-180` — a "next" arrow pointing back the way
 * you came is the single most obvious way to get right-to-left wrong.
 */
export default function DesignLoop() {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();

    const steps: Step[] = [
        {
            icon: Compass,
            title: t('Ask what the method asks'),
            body: t(
                'A framework turns principles and practices into the questions a game has to answer, phase by phase, so nothing important is skipped by accident.',
            ),
        },
        {
            icon: ListChecks,
            title: t('Answer where the answer lives'),
            body: t(
                'Structured input instead of a blank document. A choice arrives with the reasoning that produced it, and stays readable months later.',
            ),
        },
        {
            icon: Dices,
            title: t('Put a version on the table'),
            body: t(
                'Prototypes carry versions, playtests carry sittings, and what went wrong is written down while it is still true.',
            ),
        },
        {
            icon: RefreshCw,
            title: t('Close the cycle on evidence'),
            body: t(
                'An iteration ends against what the players did rather than against a hunch, and opens the next version of the game.',
            ),
        },
    ];

    return (
        <section id="how-it-works" className="scroll-mt-16 py-20 sm:py-28">
            <div className="mx-auto w-full max-w-6xl px-6">
                <SectionHeading
                    eyebrow={t('The loop')}
                    title={t('Design, then find out')}
                    description={t(
                        'Every arrow in the method is a screen in the product. None of it asks you to keep the process in your head.',
                    )}
                />

                <ol className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-4 lg:gap-6">
                    {steps.map((step, index) => (
                        <li
                            key={step.title}
                            className="relative rounded-xl border bg-card p-6 transition-colors hover:border-amber-500/40"
                        >
                            <div className="flex items-center justify-between">
                                <span className="flex size-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300">
                                    <step.icon className="size-4.5" />
                                </span>

                                <span className="text-sm text-muted-foreground tabular-nums">
                                    {formatNumber(index + 1)}
                                </span>
                            </div>

                            {/*
                             * The step that follows this one, drawn in the gap
                             * between the cards. `-end-5` is the whole reason
                             * it can be: a physical offset would put it on the
                             * wrong side of the card for a Persian reader, and
                             * the arrow itself is turned round by `rtl` so it
                             * still points at the next step rather than back
                             * at the last one.
                             */}
                            {index < steps.length - 1 && (
                                <ArrowRight
                                    aria-hidden="true"
                                    className="absolute -end-5 top-1/2 hidden size-4 -translate-y-1/2 text-muted-foreground/60 lg:block rtl:rotate-180"
                                />
                            )}

                            <h3 className="mt-5 font-semibold text-balance">
                                {step.title}
                            </h3>

                            <p className="mt-2 text-sm leading-relaxed text-pretty text-muted-foreground">
                                {step.body}
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
