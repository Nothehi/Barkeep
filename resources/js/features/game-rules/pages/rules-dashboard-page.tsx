import { Head } from '@inertiajs/react';
import type { Game, GameVersion } from '@/features/games';
import { GameHeader } from '@/features/games';
import type { Workspace } from '@/features/workspaces';
import { useTranslation } from '@/lib/i18n';
import ActionList from '../components/action-list';
import ConditionBuilder from '../components/condition-builder';
import ConditionGroupEditor from '../components/condition-group-editor';
import MechanicList from '../components/mechanic-list';
import {
    DefeatConditionEditor,
    GameEndConditionEditor,
    VictoryConditionEditor,
} from '../components/outcome-panels';
import PhaseList from '../components/phase-list';
import PhaseTransitionEditor from '../components/phase-transition-editor';
import RuleAnalysis from '../components/rule-analysis';
import RuleSetHeader from '../components/rule-set-header';
import RuleTree from '../components/rule-tree';
import TriggerEditor from '../components/trigger-editor';
import { useRulePermissions } from '../hooks/use-permissions';
import { useRuleSetScope } from '../hooks/use-rule-scope';
import type {
    EconomyChoices,
    RuleOptions,
    RuleSet,
    RuleSetAnalysis,
} from '../types/game-rules';

type RulesDashboardPageProps = {
    workspace: { data: Workspace };
    game: { data: Game };
    version: { data: GameVersion };
    ruleSet: { data: RuleSet };
    analysis: { data: RuleSetAnalysis };
    economy: EconomyChoices;
    options: RuleOptions;
};

/**
 * The whole rule system of one design state, on one screen.
 *
 * Every section reads from the same analysis, which is a deliberate departure from how some other screens
 * work. The reason is that these sections are not independent: the findings are *about* the rules, the
 * phases and the actions, and a page that fetched them separately would spend part of its life showing
 * errors about a rule set it had not finished receiving.
 *
 * It is also why every write on this page comes back as a reloaded page rather than a local update. Drawing
 * one transition can turn an unreachable phase into a reachable one and remove two findings three sections
 * further down; eight parts of this screen would otherwise hold eight different ideas of what the rules are.
 *
 * The order of the sections is the order a designer works in: what the game is made of, then the shape of a
 * turn, then what players do in it, then the sentences that everything else points at, then how it ends,
 * then what the analysis makes of all of it.
 *
 * When the rule set is in play, `canEdit` is false and every control below disables. The header carries the
 * way forward — clone it — which is why that button lives there rather than in a menu.
 */
export default function RulesDashboardPage({
    workspace: { data: workspace },
    game: { data: game },
    version: { data: version },
    ruleSet: { data: ruleSet },
    analysis: { data: analysis },
    economy,
    options,
}: RulesDashboardPageProps) {
    const { t } = useTranslation();
    const scope = useRuleSetScope(workspace, game, version, ruleSet);
    const permissions = useRulePermissions(ruleSet);
    const canEdit = permissions.canEdit;

    return (
        <>
            <Head
                title={t(':ruleSet · Rules · :game', {
                    ruleSet: ruleSet.name,
                    game: game.name,
                })}
            />

            <div className="space-y-8 px-4 py-6">
                <GameHeader game={game} workspace={workspace.slug} />

                <RuleSetHeader
                    ruleSet={ruleSet}
                    scope={scope}
                    versionLabel={version.label}
                    summary={analysis.summary}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <MechanicList
                        mechanics={analysis.mechanics}
                        options={options}
                        scope={scope}
                        canEdit={canEdit}
                    />

                    <PhaseList
                        phases={analysis.phases}
                        options={options}
                        scope={scope}
                        canEdit={canEdit}
                    />
                </div>

                <RuleTree
                    rules={analysis.rules}
                    phases={analysis.phases}
                    options={options}
                    scope={scope}
                    canEdit={canEdit}
                />

                <div className="grid gap-6 lg:grid-cols-2">
                    <PhaseTransitionEditor
                        transitions={analysis.transitions}
                        phases={analysis.phases}
                        conditions={analysis.conditions}
                        triggers={analysis.triggers}
                        scope={scope}
                        canEdit={canEdit}
                    />

                    <ActionList
                        actions={analysis.actions}
                        phases={analysis.phases}
                        options={options}
                        economy={economy}
                        scope={scope}
                        canEdit={canEdit}
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <ConditionBuilder
                        conditions={analysis.conditions}
                        options={options}
                        scope={scope}
                        canEdit={canEdit}
                    />

                    <ConditionGroupEditor
                        groups={analysis.condition_groups}
                        conditions={analysis.conditions}
                        options={options}
                        scope={scope}
                        canEdit={canEdit}
                    />
                </div>

                <TriggerEditor
                    triggers={analysis.triggers}
                    options={options}
                    scope={scope}
                    canEdit={canEdit}
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <VictoryConditionEditor
                        outcomes={analysis.victory_conditions}
                        conditions={analysis.conditions}
                        scope={scope}
                        canEdit={canEdit}
                    />

                    <DefeatConditionEditor
                        outcomes={analysis.defeat_conditions}
                        conditions={analysis.conditions}
                        scope={scope}
                        canEdit={canEdit}
                    />

                    <GameEndConditionEditor
                        outcomes={analysis.end_conditions}
                        conditions={analysis.conditions}
                        scope={scope}
                        canEdit={canEdit}
                    />
                </div>

                <RuleAnalysis
                    summary={analysis.summary}
                    errors={analysis.errors}
                    warnings={analysis.warnings}
                    scope={scope}
                />
            </div>
        </>
    );
}
