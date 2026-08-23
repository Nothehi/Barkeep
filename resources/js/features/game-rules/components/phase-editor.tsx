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
import { createPhase, updatePhase } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyPhaseInput,
    NAME_MAX_LENGTH,
    NAME_MIN_LENGTH,
    SHORT_DESCRIPTION_MAX_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    GamePhase,
    GamePhaseType,
    RuleOptions,
    RuleStatus,
} from '../types/game-rules';
import OptionSelect from './option-select';

type PhaseEditorProps = {
    scope: RuleSetScope;
    options: RuleOptions;
    phases: GamePhase[];
    phase?: GamePhase;
    disabled?: boolean;
    trigger?: React.ReactNode;
};

/**
 * Adds a stage of play, or edits one.
 *
 * A phase of the *game* — setup, the action phase, cleanup — and not a stage of the designer's work, which
 * belongs to a different module entirely and is not reachable from here.
 *
 * The type is more consequential than it looks, and the picker says so through the descriptions the server
 * sends: marking a phase as an end-game phase makes it terminal, which stops the validator asking it for an
 * exit and makes the flow draw it as a stopping point.
 *
 * No transitions on this form. The phase it would lead to usually does not exist yet, and asking for one
 * would make the first phase impossible to create.
 */
export default function PhaseEditor({
    scope,
    options,
    phases,
    phase,
    disabled,
    trigger,
}: PhaseEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const editing = phase !== undefined;

    const form = useRuleForm({
        initial: editing
            ? {
                  name: phase.name,
                  description: phase.description ?? '',
                  parent_phase_id: phase.parent_phase_id ?? '',
                  phase_type: phase.phase_type,
                  status: phase.status,
              }
            : emptyPhaseInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: NAME_MIN_LENGTH,
                    max: NAME_MAX_LENGTH,
                    tooShort: t('Name the phase.'),
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
                ? updatePhase(
                      { ...scope, gamePhase: phase.id },
                      input,
                      mutation,
                  )
                : createPhase(scope, input, mutation),
        resetOnSuccess: !editing,
        onSuccess: () => setOpen(false),
    });

    const parentOptions = phases
        .filter((candidate) => candidate.id !== phase?.id)
        .map((candidate) => ({ value: candidate.id, label: candidate.name }));

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button size="sm" disabled={disabled} data-test="add-phase">
                        <Plus className="size-4" />
                        {t('Add phase')}
                    </Button>
                )}
            </DialogTrigger>

            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {editing ? t('Edit phase') : t('Add a stage of play')}
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'Where play goes next is drawn separately, as a transition.',
                        )}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="phase-name">{t('Name')}</Label>
                        <Input
                            id="phase-name"
                            value={form.input.name}
                            dir="auto"
                            placeholder={t('Action phase')}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="phase-type">
                                {t('Kind of phase')}
                            </Label>
                            <OptionSelect
                                id="phase-type"
                                value={form.input.phase_type}
                                options={options.phase_types}
                                onChange={(value) =>
                                    form.setField(
                                        'phase_type',
                                        value as GamePhaseType,
                                    )
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                {options.phase_types.find(
                                    (option) =>
                                        option.value === form.input.phase_type,
                                )?.description ?? ''}
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phase-status">{t('Status')}</Label>
                            <OptionSelect
                                id="phase-status"
                                value={form.input.status}
                                options={options.rule_statuses}
                                onChange={(value) =>
                                    form.setField('status', value as RuleStatus)
                                }
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phase-parent">{t('Sits inside')}</Label>
                        <OptionSelect
                            id="phase-parent"
                            value={form.input.parent_phase_id}
                            options={parentOptions}
                            emptyLabel={t('Nothing — top level')}
                            onChange={(value) =>
                                form.setField('parent_phase_id', value)
                            }
                        />
                        <InputError message={form.errors.parent_phase_id} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phase-description">
                            {t('What happens')}
                        </Label>
                        <Textarea
                            id="phase-description"
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
                        {editing ? t('Save phase') : t('Add phase')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
