import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { createRule, updateRule } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    DESCRIPTION_MAX_LENGTH,
    emptyRuleInput,
    NAME_MAX_LENGTH,
    NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    GamePhase,
    GameRule,
    RuleOptions,
    RuleStatus,
    RuleType,
} from '../types/game-rules';
import OptionSelect from './option-select';

type RuleEditorProps = {
    scope: RuleSetScope;
    options: RuleOptions;
    phases: GamePhase[];
    rules: GameRule[];
    rule?: GameRule;

    /**
     * The rule the new one goes under, when the form was opened from a "add a rule here" control rather than
     * from the top of the tree.
     */
    parent?: GameRule;

    disabled?: boolean;
    trigger?: React.ReactNode;
};

/**
 * Writes a rule down, or edits one.
 *
 * Five fields, and nothing about what the rule requires or does — those are separate rows with their own
 * editors, because editing one must not be able to disturb another. A rule is written first and gated
 * afterwards, which is how designers work: "we need something about line of sight" comes before anybody has
 * worked out what it says.
 *
 * The parent picker excludes the rule itself when editing. It cannot exclude everything that *would* cycle
 * without walking the tree here, and the server refuses that case with a message on this field — which is
 * the right split: the obvious mistake is prevented, and the subtle one is explained.
 */
export default function RuleEditor({
    scope,
    options,
    phases,
    rules,
    rule,
    parent,
    disabled,
    trigger,
}: RuleEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = rule !== undefined;

    const form = useRuleForm({
        initial: editing
            ? {
                  name: rule.name,
                  description: rule.description ?? '',
                  parent_rule_id: rule.parent_rule_id ?? '',
                  phase_id: rule.phase_id ?? '',
                  rule_type: rule.rule_type,
                  status: rule.status,
              }
            : { ...emptyRuleInput, parent_rule_id: parent?.id ?? '' },
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: NAME_MIN_LENGTH,
                    max: NAME_MAX_LENGTH,
                    tooShort: t('Give the rule a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            editing
                ? updateRule({ ...scope, gameRule: rule.id }, input, mutation)
                : createRule(scope, input, mutation),
        resetOnSuccess: !editing,
        onSuccess: () => setOpen(false),
    });

    const parentOptions = rules
        .filter((candidate) => candidate.id !== rule?.id)
        .map((candidate) => ({ value: candidate.id, label: candidate.name }));

    const phaseOptions = phases.map((phase) => ({
        value: phase.id,
        label: phase.name,
    }));

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" disabled={disabled} data-test="add-rule">
                        <Plus className="size-4" />
                        {t('Add rule')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {editing ? t('Edit rule') : t('Write a rule')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'What it requires and what it does are added afterwards, on the rule itself.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="rule-name">{t('Name')}</Label>
                        <Input
                            id="rule-name"
                            value={form.input.name}
                            dir="auto"
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="rule-description">
                            {t('What it says')}
                        </Label>
                        <Textarea
                            id="rule-description"
                            rows={4}
                            value={form.input.description}
                            dir="auto"
                            onChange={(event) =>
                                form.setField('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="rule-type">
                                {t('Kind of rule')}
                            </Label>
                            <OptionSelect
                                id="rule-type"
                                value={form.input.rule_type}
                                options={options.rule_types}
                                onChange={(value) =>
                                    form.setField(
                                        'rule_type',
                                        value as RuleType,
                                    )
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="rule-status">{t('Status')}</Label>
                            <OptionSelect
                                id="rule-status"
                                value={form.input.status}
                                options={options.rule_statuses}
                                onChange={(value) =>
                                    form.setField('status', value as RuleStatus)
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="rule-parent">
                                {t('Sits under')}
                            </Label>
                            <OptionSelect
                                id="rule-parent"
                                value={form.input.parent_rule_id}
                                options={parentOptions}
                                emptyLabel={t('Nothing — top level')}
                                onChange={(value) =>
                                    form.setField('parent_rule_id', value)
                                }
                            />
                            <InputError message={form.errors.parent_rule_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="rule-phase">
                                {t('Applies during')}
                            </Label>
                            <OptionSelect
                                id="rule-phase"
                                value={form.input.phase_id}
                                options={phaseOptions}
                                emptyLabel={t('The whole game')}
                                onChange={(value) =>
                                    form.setField('phase_id', value)
                                }
                            />
                            <InputError message={form.errors.phase_id} />
                        </div>
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

                    <Button onClick={form.submit} disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {editing ? t('Save rule') : t('Add rule')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
