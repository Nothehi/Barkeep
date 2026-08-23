import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Game, GameVersion } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import EffectEditor from '../components/effect-editor';
import RequirementEditor from '../components/requirement-editor';
import RuleEditor from '../components/rule-editor';
import RuleReferenceList from '../components/rule-reference-list';
import { RuleStatusBadge } from '../components/status-badges';
import { useRulePermissions } from '../hooks/use-permissions';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    EconomyChoices,
    GamePhase,
    GameRule,
    RuleOptions,
    RuleReference,
    RuleSet,
} from '../types/game-rules';

type RulePageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    rule: { data: GameRule };
    referencedBy: { data: RuleReference[] };
    rules: { data: GameRule[] };
    phases: { data: GamePhase[] };
    economy: EconomyChoices;
    options: RuleOptions;
};

/**
 * One rule: what it says, what has to be true first, what follows from it, and what it is tangled up with.
 *
 * The "what would break if this changed" list is the reason this page exists rather than the rule being
 * edited inline in the tree. It is the question a designer asks before touching a rule, and it is answered
 * by what points *at* this one — a fact neither rule holds on its own.
 */
export default function RulePage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    rule: { data: rule },
    referencedBy: { data: referencedBy },
    rules: { data: ruleList },
    phases: { data: phases },
    economy,
    options,
}: RulePageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);
    const canEdit = useRulePermissions(ruleSet).canEdit;

    return (
        <>
            <Head
                title={t(':rule · :ruleSet · :game', {
                    rule: rule.name,
                    ruleSet: ruleSet.name,
                    game: game.name,
                })}
            />

            <div className="space-y-6 px-4 py-6">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={rules.builder.url(scope)}>
                            <ArrowLeft className="size-4 rtl:rotate-180" />
                            {t('All rules')}
                        </Link>
                    </Button>

                    {canEdit && (
                        <RuleEditor
                            scope={scope}
                            options={options}
                            phases={phases}
                            rules={ruleList}
                            rule={rule}
                            trigger={
                                <Button variant="outline" size="sm">
                                    {t('Edit rule')}
                                </Button>
                            }
                        />
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center gap-3">
                            <CardTitle dir="auto">{rule.name}</CardTitle>

                            <Badge variant="outline">
                                {rule.rule_type_label}
                            </Badge>

                            <RuleStatusBadge
                                status={rule.status}
                                label={rule.status_label}
                            />

                            {rule.phase && (
                                <span
                                    className="text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {rule.phase.name}
                                </span>
                            )}

                            <code
                                className="ms-auto text-xs text-muted-foreground"
                                dir="ltr"
                            >
                                {rule.slug}
                            </code>
                        </div>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <p className="text-sm whitespace-pre-line" dir="auto">
                            {rule.description ?? t('Nothing written down yet.')}
                        </p>

                        <RequirementEditor
                            requirements={rule.requirements ?? []}
                            options={options}
                            economy={economy}
                            scope={scope}
                            canEdit={canEdit}
                            owner={{ ruleId: rule.id }}
                        />

                        <EffectEditor
                            effects={rule.effects ?? []}
                            options={options}
                            economy={economy}
                            scope={scope}
                            canEdit={canEdit}
                            owner={{ ruleId: rule.id }}
                        />

                        <RuleReferenceList
                            rule={rule}
                            rules={ruleList}
                            references={rule.references ?? []}
                            referencedBy={referencedBy}
                            options={options}
                            scope={scope}
                            canEdit={canEdit}
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
