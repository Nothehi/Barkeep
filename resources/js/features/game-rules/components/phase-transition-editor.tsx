import { ArrowRight, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { createTransition, deleteTransition } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import { emptyTransitionInput } from '../schemas/game-rules';
import type {
    GamePhase,
    PhaseTransition,
    RuleCondition,
    RuleTrigger,
} from '../types/game-rules';
import OptionSelect from './option-select';

type PhaseTransitionEditorProps = {
    transitions: PhaseTransition[];
    phases: GamePhase[];
    conditions: RuleCondition[];
    triggers: RuleTrigger[];
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * How play moves between phases.
 *
 *     Action phase  ──  if all players have finished  ──▶  Resolution
 *
 * Both guards are optional and most transitions have neither, which is why the form does not insist: the
 * commonest edge in a board game is unconditional and automatic — the action phase simply ends and
 * resolution begins.
 *
 * A transition back to the phase it starts from is refused by the server, and the message lands on the
 * destination picker. Not prevented here, deliberately: the picker would have to hide an option that is
 * perfectly sensible-looking, and a refusal that explains itself teaches more than a missing entry.
 */
export default function PhaseTransitionEditor({
    transitions,
    phases,
    conditions,
    triggers,
    scope,
    canEdit,
}: PhaseTransitionEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyTransitionInput,
        validate: (input) => ({
            from_phase_id:
                input.from_phase_id === ''
                    ? t('Choose where play starts.')
                    : undefined,
            to_phase_id:
                input.to_phase_id === ''
                    ? t('Choose where play goes.')
                    : undefined,
        }),
        perform: (input, mutation) => createTransition(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    const phaseOptions = phases.map((phase) => ({
        value: phase.id,
        label: phase.name,
    }));

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Transitions')}</CardTitle>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={phases.length < 2}
                                data-test="add-transition"
                            >
                                <Plus className="size-4" />
                                {t('Add transition')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('How does play move on?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Leave both guards empty for a transition that simply happens.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="transition-from">
                                        {t('From')}
                                    </Label>
                                    <OptionSelect
                                        id="transition-from"
                                        value={form.input.from_phase_id}
                                        options={phaseOptions}
                                        onChange={(value) =>
                                            form.setField(
                                                'from_phase_id',
                                                value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.from_phase_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="transition-to">
                                        {t('To')}
                                    </Label>
                                    <OptionSelect
                                        id="transition-to"
                                        value={form.input.to_phase_id}
                                        options={phaseOptions}
                                        onChange={(value) =>
                                            form.setField('to_phase_id', value)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.to_phase_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="transition-condition">
                                        {t('Only when')}
                                    </Label>
                                    <OptionSelect
                                        id="transition-condition"
                                        value={form.input.condition_id}
                                        options={conditions.map(
                                            (condition) => ({
                                                value: condition.id,
                                                label: condition.statement,
                                            }),
                                        )}
                                        emptyLabel={t('No condition')}
                                        onChange={(value) =>
                                            form.setField('condition_id', value)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.condition_id}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="transition-trigger">
                                        {t('Set off by')}
                                    </Label>
                                    <OptionSelect
                                        id="transition-trigger"
                                        value={form.input.trigger_id}
                                        options={triggers.map((trigger) => ({
                                            value: trigger.id,
                                            label: trigger.name,
                                        }))}
                                        emptyLabel={t('Nothing in particular')}
                                        onChange={(value) =>
                                            form.setField('trigger_id', value)
                                        }
                                    />
                                    <InputError
                                        message={form.errors.trigger_id}
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
                                    {t('Add transition')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </CardHeader>

            <CardContent>
                {transitions.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t(
                            'Nothing says how play moves between the phases yet.',
                        )}
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {transitions.map((transition) => (
                            <li
                                key={transition.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2 text-sm"
                            >
                                <span dir="auto">
                                    {transition.from_phase_name}
                                </span>
                                <ArrowRight className="size-4 text-muted-foreground rtl:rotate-180" />
                                <span dir="auto">
                                    {transition.to_phase_name}
                                </span>

                                {transition.condition_statement && (
                                    <span
                                        className="text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {t('when :condition', {
                                            condition:
                                                transition.condition_statement,
                                        })}
                                    </span>
                                )}

                                {transition.trigger_name && (
                                    <span
                                        className="text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {transition.trigger_name}
                                    </span>
                                )}

                                {canEdit && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="ms-auto"
                                        aria-label={t('Remove transition')}
                                        onClick={() =>
                                            deleteTransition({
                                                ...scope,
                                                transition: transition.id,
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
