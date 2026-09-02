import { useFormatters, useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * How far along the arc the illustration sits. Prototyping is the fourth of
 * the eight phases, and the interesting one to show: far enough in that there
 * is something to play, early enough that the rest of the arc is still ahead.
 */
const CURRENT_PHASE = 4;

/**
 * The design arc, drawn as the product draws it.
 *
 * The hero needs a picture, and this is the honest one: no invented numbers,
 * no screenshot of a studio that does not exist. Every word in it is the
 * platform's own vocabulary — the eight phases of `DesignPhase` and the
 * description that enum gives the one being shown — so the illustration
 * cannot drift from the thing it is illustrating.
 *
 * Every phase is drawn, including the ones ahead, for the same reason the
 * dashboard draws them: the arc is the point, and it only reads if the part
 * not yet reached is visible.
 */
export default function DesignArc() {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();

    const phases = [
        { label: t('Idea'), description: t('A spark worth writing down.') },
        {
            label: t('Concept'),
            description: t('The pitch, the fantasy and the shape of a turn.'),
        },
        {
            label: t('Core design'),
            description: t('The core loop, the rules and how the game ends.'),
        },
        {
            label: t('Prototyping'),
            description: t('A playable version, however ugly.'),
        },
        {
            label: t('Playtesting'),
            description: t('Real people, real tables, real problems.'),
        },
        {
            label: t('Development'),
            description: t('Balancing, tightening and cutting.'),
        },
        {
            label: t('Production'),
            description: t('Components, art, rulebook and manufacturing.'),
        },
        { label: t('Published'), description: t('Out in the world.') },
    ];

    const current = phases[CURRENT_PHASE - 1];

    return (
        <div className="rounded-[1.25rem] bg-gradient-to-b from-amber-500/30 to-border p-px shadow-xl shadow-black/5 dark:from-amber-400/25">
            <div className="space-y-5 rounded-[1.2rem] bg-card p-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <p className="text-sm font-medium">{t('The design arc')}</p>

                    <p className="text-xs text-muted-foreground tabular-nums">
                        {t('Phase :position of :total', {
                            position: formatNumber(CURRENT_PHASE),
                            total: formatNumber(phases.length),
                        })}
                    </p>
                </div>

                {/*
                 * Presentational: the chips below say the same thing in words,
                 * and a row of eight labelled bars would only be read out
                 * twice.
                 */}
                <div className="flex gap-1.5" role="presentation">
                    {phases.map((phase, index) => (
                        <span
                            key={phase.label}
                            className={cn(
                                'h-1.5 flex-1 rounded-full',
                                index < CURRENT_PHASE
                                    ? 'bg-amber-500 dark:bg-amber-400'
                                    : 'bg-muted',
                            )}
                        />
                    ))}
                </div>

                <ul className="flex flex-wrap gap-1.5">
                    {phases.map((phase, index) => (
                        <li
                            key={phase.label}
                            className={cn(
                                'rounded-md border px-2 py-1 text-xs',
                                index === CURRENT_PHASE - 1 &&
                                    'border-amber-500/40 bg-amber-500/10 font-medium text-amber-700 dark:text-amber-300',
                                index < CURRENT_PHASE - 1 && 'text-foreground',
                                index > CURRENT_PHASE - 1 &&
                                    'text-muted-foreground',
                            )}
                        >
                            {phase.label}
                        </li>
                    ))}
                </ul>

                <div className="rounded-lg border border-dashed p-4">
                    <p className="text-sm font-medium">{current.label}</p>

                    <p className="mt-1 text-sm text-muted-foreground">
                        {current.description}
                    </p>
                </div>
            </div>
        </div>
    );
}
