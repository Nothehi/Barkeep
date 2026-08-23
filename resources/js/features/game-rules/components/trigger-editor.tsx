import { Plus, Trash2 } from 'lucide-react';
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
import { createTrigger, deleteTrigger } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyTriggerInput,
    STATEMENT_NAME_MAX_LENGTH,
    STATEMENT_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    RuleOptions,
    RuleTrigger,
    TriggerType,
} from '../types/game-rules';
import OptionSelect from './option-select';

type TriggerEditorProps = {
    triggers: RuleTrigger[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * The things that happen without anybody choosing them.
 *
 * "At the start of a round." "When the deck runs out."
 *
 * Recorded, never fired — and the form's shape is that decision made visible. There is no field here for
 * what the trigger *does*, because a trigger sitting next to an effect looks like something that wants to be
 * run, and the first line written to run it is the first line of a game engine living inside a design tool.
 * What a trigger guards is said the other way round: a transition names it.
 */
export default function TriggerEditor({
    triggers,
    options,
    scope,
    canEdit,
}: TriggerEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyTriggerInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: STATEMENT_NAME_MIN_LENGTH,
                    max: STATEMENT_NAME_MAX_LENGTH,
                    tooShort: t('Say when this happens.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createTrigger(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
                <CardTitle>{t('Triggers')}</CardTitle>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button size="sm" variant="outline">
                                <Plus className="size-4" />
                                {t('Add trigger')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('When does it happen?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Point a transition at this to say what it sets off.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="trigger-name">
                                        {t('Name')}
                                    </Label>
                                    <Input
                                        id="trigger-name"
                                        value={form.input.name}
                                        dir="auto"
                                        placeholder={t(
                                            'At the start of the round',
                                        )}
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
                                    <Label htmlFor="trigger-type">
                                        {t('Fires')}
                                    </Label>
                                    <OptionSelect
                                        id="trigger-type"
                                        value={form.input.trigger_type}
                                        options={options.trigger_types}
                                        onChange={(value) =>
                                            form.setField(
                                                'trigger_type',
                                                value as TriggerType,
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
                                    {t('Add trigger')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </CardHeader>

            <CardContent>
                {triggers.length === 0 ? (
                    <p className="py-4 text-center text-sm text-muted-foreground">
                        {t('Nothing happens automatically yet.')}
                    </p>
                ) : (
                    <ul className="space-y-2">
                        {triggers.map((trigger) => (
                            <li
                                key={trigger.id}
                                className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2"
                            >
                                <span className="text-sm" dir="auto">
                                    {trigger.name}
                                </span>

                                <Badge variant="outline">
                                    {trigger.trigger_type_label}
                                </Badge>

                                {canEdit && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="ms-auto"
                                        aria-label={t('Remove trigger')}
                                        onClick={() =>
                                            deleteTrigger({
                                                ...scope,
                                                trigger: trigger.id,
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
