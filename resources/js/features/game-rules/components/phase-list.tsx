import { ChevronDown, ChevronUp, Pencil, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import { deletePhase, reorderPhases } from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import { flattenTree, usePhaseTree } from '../hooks/use-rule-tree';
import type { GamePhase, RuleOptions } from '../types/game-rules';
import PhaseEditor from './phase-editor';
import { RuleStatusBadge } from './status-badges';

type PhaseListProps = {
    phases: GamePhase[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The stages of play, in the order play visits them.
 *
 * The order here is a rule rather than a preference: a turn structure read out of sequence is a different
 * turn structure, and the flow diagram takes the first phase as where play begins when none is marked as
 * setup. Which is why the arrows are prominent and there is no sort control.
 */
export default function PhaseList({
    phases,
    options,
    scope,
    canEdit,
}: PhaseListProps) {
    const { t } = useTranslation();
    const rows = flattenTree(usePhaseTree(phases));

    const move = (phase: GamePhase, direction: -1 | 1) => {
        const ordered = [...phases].sort((a, b) => a.position - b.position);
        const index = ordered.findIndex(
            (candidate) => candidate.id === phase.id,
        );
        const target = index + direction;

        if (target < 0 || target >= ordered.length) {
            return;
        }

        [ordered[index], ordered[target]] = [ordered[target], ordered[index]];

        reorderPhases(
            scope,
            ordered.map((candidate) => candidate.id),
        );
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Phases')}</CardTitle>

                {canEdit && (
                    <PhaseEditor
                        scope={scope}
                        options={options}
                        phases={phases}
                    />
                )}
            </CardHeader>

            <CardContent>
                {rows.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('No phases yet, so there is no shape to a turn.')}
                    </p>
                ) : (
                    <ol className="space-y-2">
                        {rows.map(({ record: phase, depth }) => (
                            <li
                                key={phase.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                                style={{
                                    marginInlineStart: `${depth * 1.25}rem`,
                                }}
                                data-test={`phase-row-${phase.slug}`}
                            >
                                <span
                                    className="text-sm font-medium"
                                    dir="auto"
                                >
                                    {phase.name}
                                </span>

                                <Badge variant="outline">
                                    {phase.phase_type_label}
                                </Badge>

                                <RuleStatusBadge
                                    status={phase.status}
                                    label={phase.status_label}
                                />

                                {(phase.actions_count ?? 0) > 0 && (
                                    <span className="text-xs text-muted-foreground">
                                        {t(':count actions', {
                                            count: phase.actions_count ?? 0,
                                        })}
                                    </span>
                                )}

                                {canEdit && (
                                    <div className="ms-auto flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Move up')}
                                            onClick={() => move(phase, -1)}
                                        >
                                            <ChevronUp className="size-4" />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Move down')}
                                            onClick={() => move(phase, 1)}
                                        >
                                            <ChevronDown className="size-4" />
                                        </Button>

                                        <PhaseEditor
                                            scope={scope}
                                            options={options}
                                            phases={phases}
                                            phase={phase}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t('Edit phase')}
                                                >
                                                    <Pencil className="size-4" />
                                                </Button>
                                            }
                                        />

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Remove phase')}
                                            onClick={() =>
                                                deletePhase({
                                                    ...scope,
                                                    gamePhase: phase.id,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
