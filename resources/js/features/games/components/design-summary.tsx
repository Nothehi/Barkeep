import { Link } from '@inertiajs/react';
import { PencilLine } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormatters, useTranslation } from '@/lib/i18n';
import games from '@/routes/games';
import type { DesignRecord } from '../types/design-record';

type DesignSummaryProps = {
    workspace: string;
    game: string;

    /** Null when nothing has been decided, which is most games. */
    record: DesignRecord | null;

    /** Whether the reader may go and change any of this. */
    canEdit: boolean;
};

/**
 * What has been decided about a game's design, read back.
 *
 * The settings form asks for thirteen things and the overview used to repeat
 * none of them, so the only way to read your own pitch was to open the form
 * that wrote it. This is that answer: the same record, rendered rather than
 * edited.
 *
 * Once anything at all has been decided, every field is drawn — including the
 * ones that have not been. An em dash where the core cost should be is the
 * useful half of this screen: the gaps are what the framework's factual
 * criteria are reading, and hiding them would make a half-designed game look
 * finished. Before anything is decided there is nothing to have gaps in, so a
 * fresh game gets an invitation instead of a grid of dashes.
 */
export default function DesignSummary({
    workspace,
    game,
    record,
    canEdit,
}: DesignSummaryProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();

    const settingsUrl = games.settings.edit({ workspace, game });

    if (record === null || record.is_empty) {
        return (
            <Card data-test="design-summary">
                <CardHeader>
                    <CardTitle>{t('Design')}</CardTitle>
                </CardHeader>

                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        {t('Nothing about the design has been decided yet.')}
                    </p>

                    {canEdit && (
                        <Link
                            href={settingsUrl}
                            className="inline-flex items-center gap-1.5 text-sm underline-offset-4 hover:underline"
                            data-test="record-design-link"
                        >
                            <PencilLine className="size-4" />
                            {t('Record the design')}
                        </Link>
                    )}
                </CardContent>
            </Card>
        );
    }

    const mechanics = record.mechanics ?? [];

    /**
     * The five parts of the loop, in the order the settings form asks for them
     * and the seeded framework reads them.
     */
    const loop = [
        [t('What the player does'), record.core_action],
        [t('What it costs'), record.core_cost],
        [t('What it gives back'), record.core_reward],
        [t('How the game is won'), record.win_condition],
        [t('How a player loses ground'), record.failure_condition],
    ] as const;

    return (
        <Card data-test="design-summary">
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <CardTitle>{t('Design')}</CardTitle>

                    {canEdit && (
                        <Link
                            href={settingsUrl}
                            className="inline-flex items-center gap-1.5 text-sm text-muted-foreground underline-offset-4 hover:underline"
                            data-test="edit-design-link"
                        >
                            <PencilLine className="size-4" />
                            {t('Edit design')}
                        </Link>
                    )}
                </div>
            </CardHeader>

            <CardContent className="space-y-6">
                <div className="space-y-4">
                    <Field label={t('One-sentence pitch')}>
                        {record.pitch}
                    </Field>

                    <Field label={t('Intended audience')}>
                        {record.audience}
                    </Field>
                </div>

                <dl
                    className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4"
                    data-test="design-constraints"
                >
                    <div>
                        <dt className="text-muted-foreground">
                            {t('Players')}
                        </dt>
                        <dd className="font-medium">
                            {record.player_count_label ?? '—'}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-muted-foreground">
                            {t('Playing time')}
                        </dt>
                        <dd className="font-medium">
                            {record.play_time_label ?? '—'}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-muted-foreground">
                            {t('Youngest player')}
                        </dt>
                        <dd className="font-medium">
                            {record.target_age_min === null
                                ? '—'
                                : t(':age and older', {
                                      age: formatNumber(record.target_age_min),
                                  })}
                        </dd>
                    </div>

                    <div>
                        <dt className="text-muted-foreground">{t('Weight')}</dt>
                        <dd className="font-medium">
                            {record.complexity_label ?? '—'}
                        </dd>
                    </div>
                </dl>

                {/*
                 * The chosen weight's description, for the same reason the form
                 * shows it: "gateway" and "hobby" are only meaningful next to
                 * what they mean.
                 */}
                {record.complexity_description && (
                    <p
                        className="text-sm text-muted-foreground"
                        data-test="design-complexity-description"
                        dir="auto"
                    >
                        {record.complexity_description}
                    </p>
                )}

                <div className="space-y-2">
                    <h3 className="text-sm font-medium">{t('Mechanics')}</h3>

                    {mechanics.length > 0 ? (
                        <ul
                            className="flex flex-wrap gap-2"
                            data-test="design-mechanics"
                        >
                            {mechanics.map((mechanic) => (
                                <li key={mechanic.id}>
                                    <Badge variant="secondary" dir="auto">
                                        {mechanic.name}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            {t('Not decided')}
                        </p>
                    )}
                </div>

                <div className="space-y-3">
                    <h3 className="text-sm font-medium">
                        {t('The core loop')}
                    </h3>

                    <dl className="grid gap-3 text-sm sm:grid-cols-2">
                        {loop.map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-muted-foreground">
                                    {label}
                                </dt>
                                <dd className="font-medium" dir="auto">
                                    {value ?? '—'}
                                </dd>
                            </div>
                        ))}
                    </dl>

                    {!record.has_complete_core_loop && (
                        <p
                            className="text-sm text-muted-foreground"
                            data-test="incomplete-core-loop"
                        >
                            {t(
                                'A loop missing a part is not one anybody has finished thinking about.',
                            )}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

/**
 * One written answer, or the fact that it has not been written.
 *
 * Prose rather than a table cell, so the pitch and the audience keep their own
 * direction — they are typed in whatever language the designer thinks in.
 */
function Field({
    label,
    children,
}: {
    label: string;
    children: string | null;
}) {
    const { t } = useTranslation();

    return (
        <div className="space-y-1">
            <h3 className="text-sm font-medium">{label}</h3>

            {children ? (
                <p className="text-sm text-muted-foreground" dir="auto">
                    {children}
                </p>
            ) : (
                <p className="text-sm text-muted-foreground">
                    {t('Not decided')}
                </p>
            )}
        </div>
    );
}
