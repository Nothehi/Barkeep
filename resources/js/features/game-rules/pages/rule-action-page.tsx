import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import ActionEditor from '../components/action-editor';
import EffectEditor from '../components/effect-editor';
import RequirementEditor from '../components/requirement-editor';
import { RuleStatusBadge } from '../components/status-badges';
import { useRulePermissions } from '../hooks/use-permissions';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    EconomyChoices,
    EconomyReference,
    GamePhase,
    RuleAction,
    RuleOptions,
    RuleSet,
} from '../types/game-rules';

type RuleActionPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    action: { data: RuleAction };
    phases: { data: GamePhase[] };
    economy: EconomyChoices;
    economyReference: { data: EconomyReference } | null;
    options: RuleOptions;
};

/**
 * One action: when it can be taken, what it needs, and what it does.
 *
 * The economy panel is the part worth reading twice. It shows what the balance profile says *today* — read
 * live through the one adapter allowed to reach that module — and nothing on this page is a stored copy of a
 * cost. Which is why the numbers here and on the balance screen are always the same numbers rather than two
 * sets that agreed when somebody last checked.
 *
 * When the handle names nothing, the panel says so rather than showing zero. An unresolved reference is
 * ordinary: rules are usually written before an economy is modelled.
 */
export default function RuleActionPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    action: { data: action },
    phases: { data: phases },
    economy,
    economyReference,
    options,
}: RuleActionPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);
    const canEdit = useRulePermissions(ruleSet).canEdit;
    const reference = economyReference?.data ?? null;

    return (
        <>
            <Head
                title={t(':action · :ruleSet · :game', {
                    action: action.name,
                    ruleSet: ruleSet.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={rules.show.url(scope)}>
                            <ArrowLeft className="size-4 rtl:rotate-180" />
                            {t('Back to the rule set')}
                        </Link>
                    </Button>

                    {canEdit && (
                        <ActionEditor
                            scope={scope}
                            options={options}
                            phases={phases}
                            economy={economy}
                            action={action}
                            trigger={
                                <Button variant="outline" size="sm">
                                    {t('Edit action')}
                                </Button>
                            }
                        />
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-3">
                            <CardTitle dir="auto">{action.name}</CardTitle>

                            <Badge variant="outline">
                                {action.action_type_label}
                            </Badge>

                            <RuleStatusBadge
                                status={action.status}
                                label={action.status_label}
                            />

                            {action.phase ? (
                                <span
                                    className="text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {action.phase.name}
                                </span>
                            ) : (
                                <Badge variant="destructive" className="gap-1">
                                    <AlertTriangle className="size-3" />
                                    {t('No phase, so nobody can take it')}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        {action.description && (
                            <p
                                className="text-sm whitespace-pre-line"
                                dir="auto"
                            >
                                {action.description}
                            </p>
                        )}

                        {reference && (
                            <section className="rounded-md border p-3">
                                <h3 className="text-sm font-medium">
                                    {t('In the economy')}
                                </h3>

                                {reference.is_resolved ? (
                                    <p className="mt-1 text-sm" dir="auto">
                                        {reference.label}

                                        {reference.summary && (
                                            <span className="text-muted-foreground">
                                                {' — '}
                                                {reference.summary}
                                            </span>
                                        )}
                                    </p>
                                ) : (
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'This points at ":handle", which is not in this version\'s balance profile.',
                                            { handle: reference.handle },
                                        )}
                                    </p>
                                )}

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {t(
                                        'The amounts live in the balance profile, not here.',
                                    )}
                                </p>
                            </section>
                        )}

                        <RequirementEditor
                            requirements={action.requirements ?? []}
                            options={options}
                            economy={economy}
                            scope={scope}
                            canEdit={canEdit}
                            owner={{ actionId: action.id }}
                        />

                        <EffectEditor
                            effects={action.effects ?? []}
                            options={options}
                            economy={economy}
                            scope={scope}
                            canEdit={canEdit}
                            owner={{ actionId: action.id }}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
