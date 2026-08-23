import { Link } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import { deleteRule, reorderRules } from '../api';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import { flattenTree, useRuleTree } from '../hooks/use-rule-tree';
import type { GameRule, GamePhase, RuleOptions } from '../types/game-rules';
import RuleEditor from './rule-editor';
import { RuleStatusBadge } from './status-badges';

type RuleTreeProps = {
    rules: GameRule[];
    phases: GamePhase[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The rulebook, as a tree.
 *
 *     Combat
 *     ├── Players declare attacks
 *     ├── Defender chooses defence
 *     ├── Resolve combat
 *     └── Apply damage
 *
 * Assembled here from a flat list, because that is how the server sends it: one query rather than one per
 * level, and — more to the point — a cycle in the data cannot make a nested *relation* recurse forever if
 * there is no nested relation. The recursion is in `useRuleTree`, which visits each rule once.
 *
 * Reordering moves one rule at a time with the arrows rather than by dragging. Two reasons: a keyboard and a
 * screen reader can both use it, and the request that goes out is still the whole ordered list — which is
 * the only shape that cannot go half-wrong when two people reorder at once.
 *
 * This is deliberately not a text editor. A rulebook typed into a textarea is a document nothing can
 * validate, nothing can clone reliably and nothing can turn into a graph, which is the whole reason this
 * module models rules as records.
 */
export default function RuleTree({
    rules: ruleList,
    phases,
    options,
    scope,
    canEdit,
}: RuleTreeProps) {
    const { t } = useTranslation();
    const tree = useRuleTree(ruleList);
    const rows = flattenTree(tree);

    const move = (rule: GameRule, direction: -1 | 1) => {
        const siblings = ruleList
            .filter(
                (candidate) => candidate.parent_rule_id === rule.parent_rule_id,
            )
            .sort((a, b) => a.position - b.position);

        const index = siblings.findIndex(
            (candidate) => candidate.id === rule.id,
        );
        const target = index + direction;

        if (target < 0 || target >= siblings.length) {
            return;
        }

        const reordered = [...siblings];
        [reordered[index], reordered[target]] = [
            reordered[target],
            reordered[index],
        ];

        reorderRules(
            scope,
            reordered.map((candidate) => candidate.id),
        );
    };

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Rules')}</CardTitle>

                {canEdit && (
                    <RuleEditor
                        scope={scope}
                        options={options}
                        phases={phases}
                        rules={ruleList}
                    />
                )}
            </CardHeader>

            <CardContent>
                {rows.length === 0 ? (
                    <p className="py-6 text-center text-sm text-muted-foreground">
                        {t('No rules written down yet.')}
                    </p>
                ) : (
                    <ul className="space-y-1">
                        {rows.map(({ record: rule, depth }) => (
                            <li
                                key={rule.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                                style={{
                                    marginInlineStart: `${depth * 1.25}rem`,
                                }}
                                data-test={`rule-row-${rule.slug}`}
                            >
                                <Link
                                    href={rules.rules.show.url({
                                        ...scope,
                                        gameRule: rule.id,
                                    })}
                                    className="text-sm font-medium hover:underline"
                                    dir="auto"
                                >
                                    {rule.name}
                                </Link>

                                <Badge variant="outline">
                                    {rule.rule_type_label}
                                </Badge>

                                <RuleStatusBadge
                                    status={rule.status}
                                    label={rule.status_label}
                                />

                                {rule.phase && (
                                    <span
                                        className="text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {rule.phase.name}
                                    </span>
                                )}

                                {(rule.effects_count ?? 0) > 0 && (
                                    <span className="text-xs text-muted-foreground">
                                        {t(':count effects', {
                                            count: rule.effects_count ?? 0,
                                        })}
                                    </span>
                                )}

                                {canEdit && (
                                    <div className="ms-auto flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Move up')}
                                            onClick={() => move(rule, -1)}
                                        >
                                            <ChevronUp className="size-4" />
                                        </Button>

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Move down')}
                                            onClick={() => move(rule, 1)}
                                        >
                                            <ChevronDown className="size-4" />
                                        </Button>

                                        <RuleEditor
                                            scope={scope}
                                            options={options}
                                            phases={phases}
                                            rules={ruleList}
                                            parent={rule}
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t(
                                                        'Add a rule under this one',
                                                    )}
                                                >
                                                    <Plus className="size-4" />
                                                </Button>
                                            }
                                        />

                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={t('Remove rule')}
                                            onClick={() =>
                                                deleteRule({
                                                    ...scope,
                                                    gameRule: rule.id,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </CardContent>
        </Card>
    );
}
