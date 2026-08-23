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
import { createAction, updateAction } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyActionInput,
    NAME_MAX_LENGTH,
    NAME_MIN_LENGTH,
    SHORT_DESCRIPTION_MAX_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    EconomyChoices,
    GamePhase,
    RuleAction,
    RuleActionType,
    RuleOptions,
    RuleStatus,
} from '../types/game-rules';
import OptionSelect from './option-select';

type ActionEditorProps = {
    scope: RuleSetScope;
    options: RuleOptions;
    phases: GamePhase[];
    economy: EconomyChoices;
    action?: RuleAction;
    disabled?: boolean;
    trigger?: React.ReactNode;
};

/**
 * Declares something a player may do, or edits it.
 *
 * The economy picker is the interesting field, and it is a *picker of handles* rather than a cost editor.
 * What Build costs belongs to GameEconomy; this form points at it and the action page shows whatever the
 * balance profile says today. There is nowhere here to type "5 wood", and that is the point — a second copy
 * of the cost is how the rules screen and the balance screen end up confidently disagreeing.
 *
 * When the version has no active balance profile the picker is not drawn at all. Most rule sets are written
 * before an economy is modelled and many studios never model one, so an empty select would be a question
 * with no answers.
 */
export default function ActionEditor({
    scope,
    options,
    phases,
    economy,
    action,
    disabled,
    trigger,
}: ActionEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = action !== undefined;

    const form = useRuleForm({
        initial: editing
            ? {
                  name: action.name,
                  description: action.description ?? '',
                  phase_id: action.phase_id ?? '',
                  action_type: action.action_type,
                  status: action.status,
                  economy_action_slug: action.economy_action_slug ?? '',
              }
            : emptyActionInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: NAME_MIN_LENGTH,
                    max: NAME_MAX_LENGTH,
                    tooShort: t('Give the action a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            description:
                validateLength(input.description, {
                    max: SHORT_DESCRIPTION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That description is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            editing
                ? updateAction(
                      { ...scope, ruleAction: action.id },
                      input,
                      mutation,
                  )
                : createAction(scope, input, mutation),
        resetOnSuccess: !editing,
        onSuccess: () => setOpen(false),
    });

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button
                        size="sm"
                        disabled={disabled}
                        data-test="add-action"
                    >
                        <Plus className="size-4" />
                        {t('Add action')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {editing
                            ? t('Edit action')
                            : t('What can a player do?')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'What it requires and what it does are added on the action itself.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="action-name">{t('Name')}</Label>
                        <Input
                            id="action-name"
                            value={form.input.name}
                            dir="auto"
                            placeholder={t('Build')}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="action-phase">
                                {t('Taken during')}
                            </Label>
                            <OptionSelect
                                id="action-phase"
                                value={form.input.phase_id}
                                options={phases.map((phase) => ({
                                    value: phase.id,
                                    label: phase.name,
                                }))}
                                emptyLabel={t('Not decided yet')}
                                onChange={(value) =>
                                    form.setField('phase_id', value)
                                }
                            />
                            <InputError message={form.errors.phase_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="action-type">
                                {t('Kind of action')}
                            </Label>
                            <OptionSelect
                                id="action-type"
                                value={form.input.action_type}
                                options={options.action_types}
                                onChange={(value) =>
                                    form.setField(
                                        'action_type',
                                        value as RuleActionType,
                                    )
                                }
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="action-status">{t('Status')}</Label>
                            <OptionSelect
                                id="action-status"
                                value={form.input.status}
                                options={options.rule_statuses}
                                onChange={(value) =>
                                    form.setField('status', value as RuleStatus)
                                }
                            />
                        </div>

                        {economy.available && (
                            <div className="space-y-2">
                                <Label htmlFor="action-economy">
                                    {t('Costs and pays')}
                                </Label>
                                <OptionSelect
                                    id="action-economy"
                                    value={form.input.economy_action_slug}
                                    options={economy.actions.map((choice) => ({
                                        value: choice.handle,
                                        label: choice.label,
                                    }))}
                                    emptyLabel={t('Nothing in the economy')}
                                    onChange={(value) =>
                                        form.setField(
                                            'economy_action_slug',
                                            value,
                                        )
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t(
                                        'The amounts stay in the balance profile.',
                                    )}
                                </p>
                            </div>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="action-description">
                            {t('What it is')}
                        </Label>
                        <Textarea
                            id="action-description"
                            rows={3}
                            value={form.input.description}
                            dir="auto"
                            onChange={(event) =>
                                form.setField('description', event.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
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
                        {editing ? t('Save action') : t('Add action')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
