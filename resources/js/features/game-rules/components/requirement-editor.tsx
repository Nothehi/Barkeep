import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { createRequirement, deleteRequirement } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import {
    emptyRequirementInput,
    REQUIREMENT_DESCRIPTION_MAX_LENGTH,
    REQUIREMENT_DESCRIPTION_MIN_LENGTH,
    validateLength,
} from '../schemas/game-rules';
import type {
    EconomyChoices,
    RequirementType,
    RuleOptions,
    RuleRequirement,
} from '../types/game-rules';
import OptionSelect from './option-select';

type RequirementEditorProps = {
    requirements: RuleRequirement[];
    options: RuleOptions;
    economy: EconomyChoices;
    scope: RuleSetScope;
    canEdit: boolean;

    /**
     * What the new requirement gates. Exactly one of the two, which is what the server insists on.
     */
    owner: { ruleId: string } | { actionId: string };
};

/**
 * What has to be true before a rule or an action applies.
 *
 * Prose with a category, and that is the whole of it. There is no expression field and no comparison
 * builder, because the moment a requirement becomes evaluable something has to evaluate it — and this module
 * describes a board game rather than playing one.
 *
 * The resource picker appears only for the `resource` type, and only when the version has an economy. It
 * stores a *handle*: the five and the wood belong to the balance profile, and putting them here would be a
 * second answer to what building costs.
 */
export default function RequirementEditor({
    requirements,
    options,
    economy,
    scope,
    canEdit,
    owner,
}: RequirementEditorProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: {
            ...emptyRequirementInput,
            rule_id: 'ruleId' in owner ? owner.ruleId : '',
            action_id: 'actionId' in owner ? owner.actionId : '',
        },
        validate: (input) => ({
            description:
                validateLength(input.description, {
                    min: REQUIREMENT_DESCRIPTION_MIN_LENGTH,
                    max: REQUIREMENT_DESCRIPTION_MAX_LENGTH,
                    tooShort: t('Say what has to be true.'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createRequirement(scope, input, mutation),
        onSuccess: () => setOpen(false),
    });

    const isEconomic =
        options.requirement_types.find(
            (option) => option.value === form.input.requirement_type,
        )?.is_economic ?? false;

    return (
        <section className="space-y-2">
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-medium">{t('Requirements')}</h3>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                variant="outline"
                                data-test="add-requirement"
                            >
                                <Plus className="size-4" />
                                {t('Add requirement')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('What has to be true first?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Written for a person to read, not for a machine to run.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="requirement-type">
                                        {t('Kind')}
                                    </Label>
                                    <OptionSelect
                                        id="requirement-type"
                                        value={form.input.requirement_type}
                                        options={options.requirement_types}
                                        onChange={(value) =>
                                            form.setField(
                                                'requirement_type',
                                                value as RequirementType,
                                            )
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="requirement-description">
                                        {t('What has to be true')}
                                    </Label>
                                    <Textarea
                                        id="requirement-description"
                                        rows={2}
                                        value={form.input.description}
                                        dir="auto"
                                        placeholder={t(
                                            'You hold at least five wood.',
                                        )}
                                        onChange={(event) =>
                                            form.setField(
                                                'description',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.description}
                                    />
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="requirement-value">
                                            {t('Threshold')}
                                        </Label>
                                        <Input
                                            id="requirement-value"
                                            value={form.input.value}
                                            dir="auto"
                                            onChange={(event) =>
                                                form.setField(
                                                    'value',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>

                                    {isEconomic && economy.available && (
                                        <div className="space-y-2">
                                            <Label htmlFor="requirement-resource">
                                                {t('Resource')}
                                            </Label>
                                            <OptionSelect
                                                id="requirement-resource"
                                                value={
                                                    form.input
                                                        .economy_resource_slug
                                                }
                                                options={economy.resources.map(
                                                    (choice) => ({
                                                        value: choice.handle,
                                                        label: choice.label,
                                                    }),
                                                )}
                                                emptyLabel={t('None')}
                                                onChange={(value) =>
                                                    form.setField(
                                                        'economy_resource_slug',
                                                        value,
                                                    )
                                                }
                                            />
                                        </div>
                                    )}
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
                                    {t('Add requirement')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>

            {requirements.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('Nothing gates this, so it can always be taken.')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {requirements.map((requirement) => (
                        <li
                            key={requirement.id}
                            className="flex flex-wrap items-start gap-2 rounded-md border px-3 py-2"
                        >
                            <Badge variant="outline">
                                {requirement.requirement_type_label}
                            </Badge>

                            <span className="text-sm" dir="auto">
                                {requirement.description}
                            </span>

                            {requirement.value && (
                                <Badge variant="secondary" dir="auto">
                                    {requirement.value}
                                </Badge>
                            )}

                            {requirement.economy_resource_slug && (
                                <Badge variant="secondary" dir="ltr">
                                    {requirement.economy_resource_slug}
                                </Badge>
                            )}

                            {canEdit && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="ms-auto"
                                    aria-label={t('Remove requirement')}
                                    onClick={() =>
                                        deleteRequirement({
                                            ...scope,
                                            requirement: requirement.id,
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
        </section>
    );
}
