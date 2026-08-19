import { Archive, Plus, Trash2 } from 'lucide-react';
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
import {
    archiveBalanceScenario,
    createBalanceScenario,
    removeScenarioVariable,
    setScenarioVariable,
    updateBalanceScenario,
} from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    emptyScenarioInput,
    isValidAmount,
    SCENARIO_NAME_MAX_LENGTH,
    SCENARIO_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-economy';
import type { BalanceScenario, BalanceVariable } from '../types/game-economy';
import Amount, { toneForNet } from './amount';
import { BalanceScenarioStatusBadge } from './status-badges';

type BalanceScenarioListProps = {
    scenarios: BalanceScenario[];
    variables: BalanceVariable[];
    scope: ProfileScope;
    canConfigure: boolean;
};

/**
 * The hypotheticals the economy is read under, and the values each one changes.
 *
 * Selecting a scenario reveals its overrides *beside the base value*, which is the whole point of the panel:
 * "15" says nothing until you can see that the profile says 10. The difference is computed by the server —
 * subtracting two decimal strings here would mean parsing them into floats, which is exactly what this
 * module refuses to do.
 *
 * Nothing on this panel can change a base variable. An override is a row in a different table, so the
 * guarantee is structural rather than something this component has to be careful about.
 */
export default function BalanceScenarioList({
    scenarios,
    variables,
    scope,
    canConfigure,
}: BalanceScenarioListProps) {
    const { t } = useTranslation();
    const [selectedId, setSelectedId] = useState<string | null>(
        scenarios[0]?.id ?? null,
    );
    const [adding, setAdding] = useState(false);

    const selected =
        scenarios.find((scenario) => scenario.id === selectedId) ?? null;

    const form = useBalanceForm({
        initial: emptyScenarioInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: SCENARIO_NAME_MIN_LENGTH,
                    max: SCENARIO_NAME_MAX_LENGTH,
                    tooShort: t('Give the scenario a name.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createBalanceScenario(scope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
                {scenarios.map((scenario) => (
                    <Button
                        key={scenario.id}
                        size="sm"
                        variant={
                            scenario.id === selectedId ? 'default' : 'outline'
                        }
                        onClick={() => setSelectedId(scenario.id)}
                        data-test={`scenario-tab-${scenario.id}`}
                    >
                        <span dir="auto">{scenario.name}</span>
                    </Button>
                ))}

                {canConfigure && !adding && (
                    <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => setAdding(true)}
                        data-test="add-scenario-button"
                    >
                        <Plus className="size-4" />
                        {t('New scenario')}
                    </Button>
                )}
            </div>

            {adding && (
                <form
                    className="grid gap-3 rounded-md border p-3 sm:grid-cols-[1fr_auto]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit();
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="scenario-name">{t('Name')}</Label>

                        <Input
                            id="scenario-name"
                            value={form.input.name}
                            onChange={(event) =>
                                form.setField('name', event.target.value)
                            }
                            placeholder={t('Rich economy')}
                            autoComplete="off"
                            data-test="scenario-name-input"
                        />

                        <InputError message={form.errors.name} />
                    </div>

                    <div className="flex items-end gap-2">
                        <Button
                            type="submit"
                            size="sm"
                            disabled={form.processing}
                            data-test="submit-scenario-button"
                        >
                            {form.processing && <Spinner />}
                            {t('Create')}
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
            )}

            {scenarios.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                    data-test="scenarios-empty"
                >
                    {t(
                        'No scenarios yet. A scenario is a situation to read the economy under: two player, late game, everybody rich.',
                    )}
                </p>
            ) : selected ? (
                <ScenarioPanel
                    scenario={selected}
                    variables={variables}
                    scope={scope}
                    canConfigure={canConfigure}
                />
            ) : null}
        </div>
    );
}

function ScenarioPanel({
    scenario,
    variables,
    scope,
    canConfigure,
}: {
    scenario: BalanceScenario;
    variables: BalanceVariable[];
    scope: ProfileScope;
    canConfigure: boolean;
}) {
    const { t } = useTranslation();
    const overrides = scenario.overrides ?? [];
    const overridden = new Set(
        overrides.map((override) => override.balance_variable_id),
    );

    const available = variables.filter(
        (variable) => !overridden.has(variable.id),
    );

    const [variableId, setVariableId] = useState(available[0]?.id ?? '');
    const [value, setValue] = useState('');
    const [saving, setSaving] = useState(false);

    const editable = canConfigure && scenario.is_modifiable;

    const add = () => {
        if (variableId === '' || !isValidAmount(value)) {
            return;
        }

        setSaving(true);

        setScenarioVariable(
            scope,
            scenario.id,
            { balance_variable_id: variableId, value },
            {
                onSuccess: () => setValue(''),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <div className="space-y-3 rounded-md border p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <div className="min-w-0 space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-medium" dir="auto">
                            {scenario.name}
                        </h3>

                        <BalanceScenarioStatusBadge
                            status={scenario.status}
                            label={scenario.status_label}
                        />
                    </div>

                    {scenario.description && (
                        <p className="text-sm text-muted-foreground" dir="auto">
                            {scenario.description}
                        </p>
                    )}
                </div>

                {editable && (
                    <div className="flex items-center gap-2">
                        {scenario.status === 'draft' && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    updateBalanceScenario(scope, scenario.id, {
                                        status: 'active',
                                    })
                                }
                                data-test={`activate-scenario-${scenario.id}`}
                            >
                                {t('Activate scenario')}
                            </Button>
                        )}

                        <Button
                            size="sm"
                            variant="ghost"
                            onClick={() =>
                                archiveBalanceScenario(scope, scenario.id)
                            }
                            data-test={`archive-scenario-${scenario.id}`}
                        >
                            <Archive className="size-3" />
                            {t('Archive')}
                        </Button>
                    </div>
                )}
            </div>

            {overrides.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('This scenario changes nothing yet.')}
                </p>
            ) : (
                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-xs text-muted-foreground">
                            <tr>
                                <th className="p-2 text-start font-medium">
                                    {t('Variable')}
                                </th>
                                <th className="p-2 text-start font-medium">
                                    {t('Base')}
                                </th>
                                <th className="p-2 text-start font-medium">
                                    {t('In this scenario')}
                                </th>
                                <th className="p-2 text-start font-medium">
                                    {t('Difference')}
                                </th>
                                <th className="p-2" />
                            </tr>
                        </thead>

                        <tbody className="divide-y">
                            {overrides.map((override) => (
                                <tr
                                    key={override.id}
                                    data-test={`override-${override.id}`}
                                >
                                    <td className="p-2" dir="auto">
                                        {override.variable_name}
                                    </td>

                                    <td className="p-2">
                                        <Amount
                                            value={override.base_value}
                                            tone="neutral"
                                        />
                                    </td>

                                    <td className="p-2 font-medium">
                                        <Amount
                                            value={override.value}
                                            unit={override.unit}
                                        />
                                    </td>

                                    <td className="p-2">
                                        <Amount
                                            value={override.delta}
                                            signed
                                            tone={
                                                override.delta
                                                    ? toneForNet(override.delta)
                                                    : undefined
                                            }
                                        />
                                    </td>

                                    <td className="p-2 text-end">
                                        {editable && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() =>
                                                    removeScenarioVariable(
                                                        scope,
                                                        scenario.id,
                                                        override.id,
                                                    )
                                                }
                                                aria-label={t(
                                                    'Remove override',
                                                )}
                                                data-test={`delete-override-${override.id}`}
                                            >
                                                <Trash2 className="size-3" />
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {editable && available.length > 0 && (
                <div className="flex flex-wrap items-end gap-2">
                    <div className="grid gap-2">
                        <Label htmlFor={`override-variable-${scenario.id}`}>
                            {t('Variable')}
                        </Label>

                        <Select
                            value={variableId}
                            onValueChange={setVariableId}
                        >
                            <SelectTrigger
                                id={`override-variable-${scenario.id}`}
                                className="w-56"
                                data-test="override-variable-picker"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {available.map((variable) => (
                                    <SelectItem
                                        key={variable.id}
                                        value={variable.id}
                                    >
                                        {variable.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`override-value-${scenario.id}`}>
                            {t('Value')}
                        </Label>

                        <Input
                            id={`override-value-${scenario.id}`}
                            value={value}
                            onChange={(event) => setValue(event.target.value)}
                            inputMode="decimal"
                            dir="ltr"
                            className="w-28"
                            autoComplete="off"
                            data-test="override-value-input"
                        />
                    </div>

                    <Button
                        size="sm"
                        onClick={add}
                        disabled={saving || !isValidAmount(value)}
                        data-test="submit-override-button"
                    >
                        {saving && <Spinner />}
                        {t('Override')}
                    </Button>
                </div>
            )}
        </div>
    );
}
