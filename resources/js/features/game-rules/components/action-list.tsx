import { Link } from '@inertiajs/react';
import { AlertTriangle, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import { deleteAction } from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import type {
    EconomyChoices,
    GamePhase,
    RuleAction,
    RuleOptions,
} from '../types/game-rules';
import ActionEditor from './action-editor';
import { RuleStatusBadge } from './status-badges';

type ActionListProps = {
    actions: RuleAction[];
    phases: GamePhase[];
    options: RuleOptions;
    economy: EconomyChoices;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The things a player may do.
 *
 * An action with no phase is marked here rather than only in the findings, because it is the shape a
 * designer most often creates by accident: the action gets added, the turn structure gets settled later, and
 * in between it is something nobody can place in the turn.
 *
 * The counts are the server's, not lengths of loaded arrays. An actions list that fetched every requirement
 * to render "2" would be two queries per row.
 */
export default function ActionList({
    actions,
    phases,
    options,
    economy,
    scope,
    canEdit,
}: ActionListProps) {
    const { t } = useTranslation();

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Actions')}</CardTitle>

                {canEdit && (
                    <ActionEditor
                        scope={scope}
                        options={options}
                        phases={phases}
                        economy={economy}
                    />
                )}
            </CardHeader>

            <CardContent>
                {actions.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('Players cannot do anything yet.')}
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {actions.map((action) => (
                            <li
                                key={action.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                                data-test={`action-row-${action.slug}`}
                            >
                                <Link
                                    href={rules.actions.show.url({
                                        ...scope,
                                        ruleAction: action.id,
                                    })}
                                    className="text-sm font-medium hover:underline"
                                    dir="auto"
                                >
                                    {action.name}
                                </Link>

                                <Badge variant="outline">
                                    {action.action_type_label}
                                </Badge>

                                <RuleStatusBadge
                                    status={action.status}
                                    label={action.status_label}
                                />

                                {action.phase ? (
                                    <span
                                        className="text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {action.phase.name}
                                    </span>
                                ) : (
                                    <Badge
                                        variant="destructive"
                                        className="gap-1"
                                    >
                                        <AlertTriangle className="size-3" />
                                        {t('No phase')}
                                    </Badge>
                                )}

                                {action.economy_action_slug && (
                                    <Badge variant="secondary" dir="ltr">
                                        {action.economy_action_slug}
                                    </Badge>
                                )}

                                <span className="text-xs text-muted-foreground">
                                    {t(
                                        ':requirements requirements · :effects effects',
                                        {
                                            requirements:
                                                action.requirements_count ?? 0,
                                            effects: action.effects_count ?? 0,
                                        },
                                    )}
                                </span>

                                {canEdit && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="ms-auto"
                                        aria-label={t('Remove action')}
                                        onClick={() =>
                                            deleteAction({
                                                ...scope,
                                                ruleAction: action.id,
                                            })
                                        }
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
