import { ArrowDown, Check, Users } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';

/**
 * Why the unit of work is a studio rather than a file.
 *
 * The distinction this section has to land is the one the domain is built on:
 * games, prototypes and playtests belong to a workspace, while the mechanics
 * list and the frameworks belong to the platform. That is what keeps two
 * studios comparable, and it is not something a visitor would guess — so it
 * is drawn as well as said.
 *
 * The arrow in that drawing points down rather than along, which is the one
 * direction that means the same thing on both sides of the page.
 */
export default function StudioSection() {
    const { t } = useTranslation();

    const points = [
        t(
            'Invite the people you design with, and say what each of them may change.',
        ),
        t('Move between studios without losing your place in either.'),
        t(
            'The vocabulary and the frameworks stay shared, so nothing you learn is trapped in one project.',
        ),
    ];

    const owned = [t('Games'), t('Prototypes'), t('Playtests')];
    const shared = [t('Mechanics'), t('Frameworks')];

    return (
        <section className="py-20 sm:py-28">
            <div className="mx-auto grid w-full max-w-6xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
                <div>
                    <p className="inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-400">
                        <Users className="size-4" />
                        {t('For studios')}
                    </p>

                    <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl">
                        {t('Built around a studio, not a folder')}
                    </h2>

                    <p className="mt-4 text-base leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'A workspace is who you design with. Everything a project accumulates belongs to it, and switching between two of them is a choice rather than a migration.',
                        )}
                    </p>

                    <ul className="mt-8 space-y-4">
                        {points.map((point) => (
                            <li key={point} className="flex gap-3">
                                <Check className="mt-0.5 size-4.5 shrink-0 text-amber-600 dark:text-amber-400" />

                                <span className="text-sm leading-relaxed text-pretty">
                                    {point}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="rounded-2xl border bg-muted/40 p-6 sm:p-8">
                    <div className="rounded-xl border bg-card p-5">
                        <p className="text-xs text-muted-foreground">
                            {t('The studio')}
                        </p>

                        <ul className="mt-3 flex flex-wrap gap-2">
                            {owned.map((item) => (
                                <li key={item}>
                                    <Badge variant="secondary">{item}</Badge>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <ArrowDown
                        aria-hidden="true"
                        className="mx-auto my-3 size-4 text-muted-foreground"
                    />

                    <div className="rounded-xl border border-dashed bg-card p-5">
                        <p className="text-xs text-muted-foreground">
                            {t('The platform')}
                        </p>

                        <ul className="mt-3 flex flex-wrap gap-2">
                            {shared.map((item) => (
                                <li key={item}>
                                    <Badge variant="outline">{item}</Badge>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="mt-6 text-sm leading-relaxed text-pretty text-muted-foreground">
                        {t(
                            'One studio, one vocabulary, and a record of every version anybody at the table has played.',
                        )}
                    </p>
                </div>
            </div>
        </section>
    );
}
