import { Link } from '@inertiajs/react';
import {
    Copy,
    GitBranch,
    ListTree,
    Play,
    Archive,
    Workflow,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import rules from '@/routes/rules';
import { activateRuleSet, archiveRuleSet, cloneRuleSet } from '../api';
import { useRulePermissions } from '../hooks/use-permissions';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import type { RuleSet, RuleSetSummary } from '../types/game-rules';
import { RuleSetStatusBadge } from './status-badges';

type RuleSetHeaderProps = {
    ruleSet: RuleSet;
    scope: RuleSetScope;
    versionLabel: string;
    summary?: RuleSetSummary;
};

/**
 * The heading of every rules screen: which rule system this is, where it sits in its life, and the four ways
 * out of here.
 *
 * The interesting part is what happens when the rule set is in play. `canEdit` goes false and almost every
 * control on every screen below this one disables — so this header has to offer the way forward, or a
 * designer is left looking at a read-only page with no explanation. "Clone to a new draft" is that way, and
 * it is why the button is here rather than buried in a menu.
 *
 * Activation is refused while the validator reports errors, and the button says so rather than letting
 * somebody find out by pressing it.
 */
export default function RuleSetHeader({
    ruleSet,
    scope,
    versionLabel,
    summary,
}: RuleSetHeaderProps) {
    const { t } = useTranslation();
    const permissions = useRulePermissions(ruleSet);
    const [working, setWorking] = useState(false);

    const blockedByErrors = (summary?.errors ?? 0) > 0;

    return (
        <Card>
            <CardContent className="flex flex-wrap items-start justify-between gap-4 px-6 py-5">
                <div className="space-y-1">
                    <div className="flex flex-wrap items-center gap-3">
                        <h2 className="text-lg font-semibold" dir="auto">
                            {ruleSet.name}
                        </h2>

                        <RuleSetStatusBadge
                            status={ruleSet.status}
                            label={ruleSet.status_label}
                        />

                        <span className="text-sm text-muted-foreground">
                            {versionLabel}
                        </span>
                    </div>

                    {ruleSet.description && (
                        <p
                            className="max-w-2xl text-sm text-muted-foreground"
                            dir="auto"
                        >
                            {ruleSet.description}
                        </p>
                    )}

                    {!ruleSet.is_editable && permissions.canClone && (
                        <p className="text-sm text-muted-foreground">
                            {t(
                                'These rules are settled. Clone them to a new draft to make changes.',
                            )}
                        </p>
                    )}
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={rules.builder.url(scope)}>
                            <ListTree className="size-4" />
                            {t('Builder')}
                        </Link>
                    </Button>

                    <Button variant="outline" size="sm" asChild>
                        <Link href={rules.phases.url(scope)}>
                            <Workflow className="size-4" />
                            {t('Phases')}
                        </Link>
                    </Button>

                    <Button variant="outline" size="sm" asChild>
                        <Link href={rules.graph.url(scope)}>
                            <GitBranch className="size-4" />
                            {t('Flow')}
                        </Link>
                    </Button>

                    {permissions.canClone && (
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={working}
                            onClick={() => {
                                setWorking(true);
                                cloneRuleSet(
                                    scope,
                                    {},
                                    { onFinish: () => setWorking(false) },
                                );
                            }}
                            data-test="clone-rule-set"
                        >
                            <Copy className="size-4" />
                            {t('Clone to a new draft')}
                        </Button>
                    )}

                    {permissions.canActivate && (
                        <Button
                            size="sm"
                            disabled={working || blockedByErrors}
                            title={
                                blockedByErrors
                                    ? t(
                                          'Fix the errors below before putting these rules into play.',
                                      )
                                    : undefined
                            }
                            onClick={() => {
                                setWorking(true);
                                activateRuleSet(scope, {
                                    onFinish: () => setWorking(false),
                                });
                            }}
                            data-test="activate-rule-set"
                        >
                            <Play className="size-4" />
                            {t('These are the rules')}
                        </Button>
                    )}

                    {permissions.canArchive && (
                        <Button
                            variant="ghost"
                            size="sm"
                            disabled={working}
                            onClick={() => {
                                setWorking(true);
                                archiveRuleSet(scope, {
                                    onFinish: () => setWorking(false),
                                });
                            }}
                            data-test="archive-rule-set"
                        >
                            <Archive className="size-4" />
                            {t('Archive')}
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
