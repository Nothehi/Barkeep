import type { LucideIcon } from 'lucide-react';
import {
    Blocks,
    ClipboardList,
    Gamepad2,
    Layers,
    Library,
    Scale,
} from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import SectionHeading from './section-heading';

type Feature = {
    icon: LucideIcon;
    title: string;
    body: string;
};

/**
 * What a game accumulates once it is being designed here.
 *
 * One card per bounded context the workspace actually has today — games,
 * mechanics, frameworks, prototypes, playtests, rules and balance. Nothing
 * on this list is a plan: a landing page that promises a screen the product
 * does not have is a bug report waiting to be filed.
 */
export default function FeatureGrid() {
    const { t } = useTranslation();

    const features: Feature[] = [
        {
            icon: Gamepad2,
            title: t('Games and their versions'),
            body: t(
                'A game carries its status and its place on the design arc, and every version cut from it stays readable beside the ones before it.',
            ),
        },
        {
            icon: Blocks,
            title: t('A vocabulary you share'),
            body: t(
                'Mechanics belong to the platform rather than to one studio. Two games that both use worker placement say so with the same word, which is the only reason they can be compared at all.',
            ),
        },
        {
            icon: Library,
            title: t('Frameworks, versioned'),
            body: t(
                'Principles, practices, phases, prompts and checklists. Improving the method cuts a new version of it instead of quietly rewriting the games designed under the old one.',
            ),
        },
        {
            icon: Layers,
            title: t('Prototypes and iterations'),
            body: t(
                'Cycles you plan, run and close, each one pointed at a particular prototype version rather than at the game in general.',
            ),
        },
        {
            icon: ClipboardList,
            title: t('Playtests that outlive the evening'),
            body: t(
                'Sessions, the people at the table, and what came out of them, recorded rather than remembered a week later.',
            ),
        },
        {
            icon: Scale,
            title: t('Rules and balance'),
            body: t(
                'Rule sets, actions and resources with balance profiles you can hold up against an earlier version and argue about with numbers.',
            ),
        },
    ];

    return (
        <section
            id="workspace"
            className="scroll-mt-16 border-y bg-muted/30 py-20 sm:py-28"
        >
            <div className="mx-auto w-full max-w-6xl px-6">
                <SectionHeading
                    eyebrow={t('Inside the workspace')}
                    title={t('A game is not a document')}
                    description={t(
                        'It is versions, rules, resources, prototypes and the sittings that changed them. All of it kept where the next decision is made.',
                    )}
                />

                <div className="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {features.map((feature) => (
                        <article
                            key={feature.title}
                            className="rounded-xl border bg-card p-6 transition-colors hover:border-amber-500/40"
                        >
                            <feature.icon className="size-5 text-amber-600 dark:text-amber-400" />

                            <h3 className="mt-4 font-semibold text-balance">
                                {feature.title}
                            </h3>

                            <p className="mt-2 text-sm leading-relaxed text-pretty text-muted-foreground">
                                {feature.body}
                            </p>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
