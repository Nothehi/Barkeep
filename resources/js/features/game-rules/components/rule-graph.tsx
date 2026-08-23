import { AlertTriangle, ArrowDown, CircleDot, Flag } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import type { Outcome, RuleGraph as RuleGraphData } from '../types/game-rules';

type RuleGraphProps = {
    graph: RuleGraphData;
    victoryConditions?: Outcome[];
    endConditions?: Outcome[];
};

/**
 * The flow of a game, drawn.
 *
 * A vertical run of boxes with the arrows between them labelled, which is what a board game's turn structure
 * actually looks like: mostly linear, with a loop back to the top of the round and one branch at the end.
 * There is no canvas and no graph library — section 45 of the module brief says not to add one unless the
 * project already has it, and it does not.
 *
 * That constraint turns out to be the right shape rather than a compromise. A phase with several exits draws
 * its arrows as a labelled list under the box, which is how a rulebook words it too: "if somebody has won, go
 * to game end; otherwise back to round start."
 *
 * Read-only, deliberately. The phase designer edits phases and transitions; this is what those *are* when
 * drawn, and a canvas that also had to support dragging and connecting would spend its space on affordances
 * rather than on the shape of the game.
 *
 * Arrows the rule set did not state — mainly the one into the first phase — are drawn faintly, so somebody
 * can tell what they wrote from what was inferred on their behalf.
 */
export default function RuleGraph({
    graph,
    victoryConditions = [],
    endConditions = [],
}: RuleGraphProps) {
    const { t } = useTranslation();

    if (graph.is_empty) {
        return (
            <Card>
                <CardContent className="px-6 py-8 text-center text-sm text-muted-foreground">
                    {t(
                        'Add a phase or two and the flow of the game will appear here.',
                    )}
                </CardContent>
            </Card>
        );
    }

    const edgesFrom = (key: string) =>
        graph.edges.filter((edge) => edge.from === key);

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('How play moves')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-1 overflow-x-auto">
                <div className="min-w-[22rem] space-y-1">
                    {graph.nodes.map((node) => (
                        <div key={node.key} className="space-y-1">
                            <div
                                className={[
                                    'rounded-md border px-4 py-3',
                                    node.is_reachable
                                        ? ''
                                        : 'border-dashed opacity-70',
                                    node.is_entry ? 'border-primary/50' : '',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                                data-test={`graph-node-${node.key}`}
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    {node.is_terminal ? (
                                        <Flag className="size-4 text-muted-foreground" />
                                    ) : (
                                        <CircleDot className="size-4 text-muted-foreground" />
                                    )}

                                    <span
                                        className="text-sm font-medium"
                                        dir="auto"
                                    >
                                        {node.label}
                                    </span>

                                    {node.detail && (
                                        <span className="text-xs text-muted-foreground">
                                            {node.detail}
                                        </span>
                                    )}

                                    {!node.is_reachable && (
                                        <Badge
                                            variant="outline"
                                            className="gap-1"
                                        >
                                            <AlertTriangle className="size-3" />
                                            {t('Play never gets here')}
                                        </Badge>
                                    )}
                                </div>

                                {node.actions.length > 0 && (
                                    <ul className="mt-2 flex flex-wrap gap-1.5">
                                        {node.actions.map((action) => (
                                            <li key={action}>
                                                <Badge
                                                    variant="secondary"
                                                    dir="auto"
                                                >
                                                    {action}
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            {edgesFrom(node.key).map((edge) => (
                                <div
                                    key={`${edge.from}-${edge.to}-${edge.entity_id ?? 'implicit'}`}
                                    className={[
                                        'flex items-center gap-2 ps-4 text-xs',
                                        edge.is_implicit
                                            ? 'text-muted-foreground/60'
                                            : 'text-muted-foreground',
                                    ].join(' ')}
                                >
                                    <ArrowDown className="size-3.5" />

                                    <span dir="auto">
                                        {edge.label ??
                                            (edge.is_implicit
                                                ? t('assumed')
                                                : t('always'))}
                                    </span>

                                    <span className="text-muted-foreground/60">
                                        →{' '}
                                        {graph.nodes.find(
                                            (node) => node.key === edge.to,
                                        )?.label ?? edge.to}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ))}
                </div>

                {(victoryConditions.length > 0 || endConditions.length > 0) && (
                    <div className="mt-6 space-y-3 border-t pt-4">
                        {endConditions.length > 0 && (
                            <OutcomeRow
                                heading={t('The game ends when')}
                                outcomes={endConditions}
                            />
                        )}

                        {victoryConditions.length > 0 && (
                            <OutcomeRow
                                heading={t('You win by')}
                                outcomes={victoryConditions}
                            />
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * The outcomes, listed under the flow.
 *
 * A run of boxes that stops at "Cleanup" does not show how the game finishes, and "what ends this?" is the
 * question somebody opens this screen to answer.
 */
function OutcomeRow({
    heading,
    outcomes,
}: {
    heading: string;
    outcomes: Outcome[];
}) {
    return (
        <div>
            <p className="text-xs font-medium text-muted-foreground">
                {heading}
            </p>

            <ul className="mt-1 space-y-1">
                {outcomes.map((outcome) => (
                    <li key={outcome.id} className="text-sm" dir="auto">
                        {outcome.name}

                        {outcome.condition_statement && (
                            <span className="text-muted-foreground">
                                {' — '}
                                {outcome.condition_statement}
                            </span>
                        )}
                    </li>
                ))}
            </ul>
        </div>
    );
}
