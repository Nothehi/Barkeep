import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { createFlow, deleteFlow } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    CONDITION_MAX_LENGTH,
    emptyFlowInput,
    FLOW_NAME_MAX_LENGTH,
    FLOW_NAME_MIN_LENGTH,
    validateAmount,
    validateLength,
} from '../schemas/game-economy';
import type {
    BalanceOptions,
    ResourceFlow,
    ResourceFlowType,
    ResourceType,
} from '../types/game-economy';
import Amount from './amount';
import { FlowTypeBadge } from './status-badges';

type ResourceFlowListProps = {
    flows: ResourceFlow[];
    resources: ResourceType[];
    scope: ProfileScope;
    options: BalanceOptions;
    canConfigure: boolean;
    /**
     * Narrow the list to one resource, as the resource page does.
     */
    resourceId?: string;
};

/**
 * How the resources move, and the form that adds another.
 *
 * The condition is prose — "per round", "when a worker is placed" — and it is a plain text field on purpose.
 * This module models an economy rather than executing one; an expression language here would be a simulator
 * wearing a text box, and the brief is explicit that simulation is a different bounded context if it ever
 * arrives.
 *
 * The amount is entered as a magnitude and the direction comes from the type beside it, which is why there
 * is no minus sign to type. A stored "-2 generation" would be a row that contradicts itself.
 */
export default function ResourceFlowList({
    flows,
    resources,
    scope,
    options,
    canConfigure,
    resourceId,
}: ResourceFlowListProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const visible = resourceId
        ? flows.filter((flow) => flow.resource_type_id === resourceId)
        : flows;

    const form = useBalanceForm({
        initial: {
            ...emptyFlowInput,
            resource_type_id: resourceId ?? resources[0]?.id ?? '',
        },
        validate: (input) => ({
            resource_type_id:
                input.resource_type_id === ''
                    ? t('Choose a resource.')
                    : undefined,
            name:
                validateLength(input.name, {
                    min: FLOW_NAME_MIN_LENGTH,
                    max: FLOW_NAME_MAX_LENGTH,
                    tooShort: t('Give the flow a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
            amount:
                validateAmount(input.amount, {
                    required: true,
                    allowNegative: false,
                    missing: t('How much moves?'),
                    malformed: t(
                        'Write this as a plain number, such as 5 or 2.5.',
                    ),
                    negative: t(
                        'Write the amount as a positive number — the flow type says which way it goes.',
                    ),
                    tooPrecise: t('That is more decimal places than we keep.'),
                }) ?? undefined,
            condition:
                validateLength(input.condition, {
                    max: CONDITION_MAX_LENGTH,
                    tooShort: '',
                    tooLong: t('That condition is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) => createFlow(scope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    return (
        <div className="space-y-3">
            {visible.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    data-test="flows-empty"
                >
                    {t(
                        'No flows yet. A flow is how a resource arrives or leaves: income, upkeep, spoilage.',
                    )}
                </p>
            ) : (
                <ul className="divide-y rounded-md border">
                    {visible.map((flow) => (
                        <li
                            key={flow.id}
                            className="flex flex-wrap items-center justify-between gap-3 p-3"
                            data-test={`flow-row-${flow.id}`}
                        >
                            <div className="min-w-0 space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium" dir="auto">
                                        {flow.name}
                                    </span>

                                    <FlowTypeBadge
                                        flowType={flow.flow_type}
                                        label={flow.flow_type_label}
                                        direction={flow.direction}
                                    />
                                </div>

                                <p className="text-xs text-muted-foreground">
                                    <span dir="auto">{flow.resource_name}</span>
                                    {flow.condition ? (
                                        <>
                                            {' · '}
                                            <span dir="auto">
                                                {flow.condition}
                                            </span>
                                        </>
                                    ) : null}
                                </p>
                            </div>

                            <div className="flex items-center gap-3">
                                <Amount
                                    value={flow.signed_amount}
                                    signed
                                    tone={
                                        flow.direction > 0
                                            ? 'positive'
                                            : flow.direction < 0
                                              ? 'negative'
                                              : 'neutral'
                                    }
                                    className="font-medium"
                                />

                                {canConfigure && (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() =>
                                            deleteFlow(scope, flow.id)
                                        }
                                        aria-label={t('Remove flow')}
                                        data-test={`delete-flow-${flow.id}`}
                                    >
                                        <Trash2 className="size-3" />
                                    </Button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {canConfigure && resources.length > 0 && (
                <>
                    {adding ? (
                        <form
                            className="grid gap-3 rounded-md border p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.submit();
                            }}
                        >
                            <div className="grid gap-3 sm:grid-cols-2">
                                {!resourceId && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="flow-resource">
                                            {t('Resource')}
                                        </Label>

                                        <Select
                                            value={form.input.resource_type_id}
                                            onValueChange={(value) =>
                                                form.setField(
                                                    'resource_type_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                id="flow-resource"
                                                data-test="flow-resource-picker"
                                            >
                                                <SelectValue />
                                            </SelectTrigger>

                                            <SelectContent>
                                                {resources.map((resource) => (
                                                    <SelectItem
                                                        key={resource.id}
                                                        value={resource.id}
                                                    >
                                                        {resource.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <InputError
                                            message={
                                                form.errors.resource_type_id
                                            }
                                        />
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="flow-type">
                                        {t('Flow type')}
                                    </Label>

                                    <Select
                                        value={form.input.flow_type}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'flow_type',
                                                value as ResourceFlowType,
                                            )
                                        }
                                    >
                                        <SelectTrigger
                                            id="flow-type"
                                            data-test="flow-type-picker"
                                        >
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.flow_types.map((type) => (
                                                <SelectItem
                                                    key={type.value}
                                                    value={type.value}
                                                >
                                                    {type.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-3">
                                <div className="grid gap-2 sm:col-span-2">
                                    <Label htmlFor="flow-name">
                                        {t('Name')}
                                    </Label>

                                    <Input
                                        id="flow-name"
                                        value={form.input.name}
                                        onChange={(event) =>
                                            form.setField(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t('Harvest')}
                                        autoComplete="off"
                                        data-test="flow-name-input"
                                    />

                                    <InputError message={form.errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="flow-amount">
                                        {t('Amount')}
                                    </Label>

                                    <Input
                                        id="flow-amount"
                                        value={form.input.amount}
                                        onChange={(event) =>
                                            form.setField(
                                                'amount',
                                                event.target.value,
                                            )
                                        }
                                        inputMode="decimal"
                                        dir="ltr"
                                        placeholder="3"
                                        autoComplete="off"
                                        data-test="flow-amount-input"
                                    />

                                    <InputError message={form.errors.amount} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="flow-condition">
                                    {t('When does it happen?')}{' '}
                                    <span className="font-normal text-muted-foreground">
                                        {t('(optional)')}
                                    </span>
                                </Label>

                                <Input
                                    id="flow-condition"
                                    value={form.input.condition}
                                    onChange={(event) =>
                                        form.setField(
                                            'condition',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t('per round')}
                                    autoComplete="off"
                                />

                                <InputError message={form.errors.condition} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test="submit-flow-button"
                                >
                                    {form.processing && <Spinner />}
                                    {t('Add flow')}
                                </Button>

                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        setAdding(false);
                                        form.reset();
                                    }}
                                >
                                    {t('Cancel')}
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setAdding(true)}
                            data-test="add-flow-button"
                        >
                            <Plus className="size-4" />
                            {t('Add flow')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
