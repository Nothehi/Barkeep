import { Plus, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import {
    addConditionToGroup,
    createConditionGroup,
    deleteConditionGroup,
    removeConditionFromGroup,
} from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyConditionGroupInput,
    STATEMENT_NAME_MAX_LENGTH,
    STATEMENT_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    ConditionGroup,
    LogicOperator,
    RuleCondition,
    RuleOptions,
} from '../types/game-rules';
import OptionSelect from './option-select';

type ConditionGroupEditorProps = {
    groups: ConditionGroup[];
    conditions: RuleCondition[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * Several conditions, combined by one operator.
 *
 *     All of these
 *       ├── Wood is at least 5
 *       └── Player owns Workshop
 *
 * Flat, and staying flat. There is no control here for a nested group and deliberately nowhere to put one —
 * an arbitrary tree needs a parser, a renderer and a precedence rule, and a studio that needs one needs
 * something that can evaluate it too.
 *
 * A condition is removed by its *membership* rather than by its id, because the same condition may be in
 * several groups and detaching it from one must not touch the others.
 */
export default function ConditionGroupEditor({
    groups,
    conditions,
    options,
    scope,
    canEdit,
}: ConditionGroupEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyConditionGroupInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: STATEMENT_NAME_MIN_LENGTH,
                    max: STATEMENT_NAME_MAX_LENGTH,
                    tooShort: t('Name the group.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createConditionGroup(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Condition groups')}</CardTitle>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button size="sm" variant="outline">
                                <Plus className="size-4" />
                                {t('Add group')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('Combine some conditions')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Pick the conditions after the group exists.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="group-name">
                                        {t('Name')}
                                    </Label>
                                    <Input
                                        id="group-name"
                                        value={form.input.name}
                                        dir="auto"
                                        onChange={(event) =>
                                            form.setField(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="group-operator">
                                        {t('How they combine')}
                                    </Label>
                                    <OptionSelect
                                        id="group-operator"
                                        value={form.input.logic_operator}
                                        options={options.logic_operators}
                                        onChange={(value) =>
                                            form.setField(
                                                'logic_operator',
                                                value as LogicOperator,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    variant="ghost"
                                    onClick={() => setOpen(false)}
                                    disabled={form.processing}
                                >
                                    {t('Cancel')}
                                </Button>

                                <Button
                                    onClick={form.submit}
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    {t('Add group')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </CardHeader>

            <CardContent>
                {groups.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('No groups yet.')}
                    </p>
                ) : (
                    <ul className="space-y-3">
                        {groups.map((group) => (
                            <li
                                key={group.id}
                                className="rounded-md border px-3 py-2"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className="text-sm font-medium"
                                        dir="auto"
                                    >
                                        {group.name}
                                    </span>

                                    <Badge variant="outline">
                                        {group.logic_operator_label}
                                    </Badge>

                                    {canEdit && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="ms-auto"
                                            aria-label={t('Remove group')}
                                            onClick={() =>
                                                deleteConditionGroup({
                                                    ...scope,
                                                    conditionGroup: group.id,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    )}
                                </div>

                                <ul className="mt-2 space-y-1">
                                    {(group.memberships ?? []).map(
                                        (membership) => (
                                            <li
                                                key={membership.id}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <span className="text-muted-foreground">
                                                    {group.joiner}
                                                </span>

                                                <span dir="auto">
                                                    {conditions.find(
                                                        (condition) =>
                                                            condition.id ===
                                                            membership.condition_id,
                                                    )?.statement ??
                                                        membership.condition_id}
                                                </span>

                                                {canEdit && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={t(
                                                            'Take out of the group',
                                                        )}
                                                        onClick={() =>
                                                            removeConditionFromGroup(
                                                                {
                                                                    ...scope,
                                                                    conditionGroup:
                                                                        group.id,
                                                                    membership:
                                                                        membership.id,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        <X className="size-3.5" />
                                                    </Button>
                                                )}
                                            </li>
                                        ),
                                    )}
                                </ul>

                                {canEdit && conditions.length > 0 && (
                                    <div className="mt-2 max-w-xs">
                                        <OptionSelect
                                            value=""
                                            emptyLabel={t('Add a condition…')}
                                            options={conditions
                                                .filter(
                                                    (condition) =>
                                                        !(
                                                            group.memberships ??
                                                            []
                                                        ).some(
                                                            (membership) =>
                                                                membership.condition_id ===
                                                                condition.id,
                                                        ),
                                                )
                                                .map((condition) => ({
                                                    value: condition.id,
                                                    label: condition.statement,
                                                }))}
                                            onChange={(value) => {
                                                if (value !== '') {
                                                    addConditionToGroup(
                                                        {
                                                            ...scope,
                                                            conditionGroup:
                                                                group.id,
                                                        },
                                                        value,
                                                    );
                                                }
                                            }}
                                        />
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
