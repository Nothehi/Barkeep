import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { createBalanceObservation } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    emptyObservationInput,
    OBSERVATION_BODY_MAX_LENGTH,
    OBSERVATION_BODY_MIN_LENGTH,
    OBSERVATION_TITLE_MAX_LENGTH,
    OBSERVATION_TITLE_MIN_LENGTH,
    validateLength,
} from '../schemas/game-economy';
import type {
    BalanceObservation,
    BalanceOptions,
    ObservationSeverity,
    ObservationSourceType,
} from '../types/game-economy';
import { ObservationSeverityBadge } from './status-badges';

type BalanceObservationListProps = {
    observations: BalanceObservation[];
    scope: ProfileScope;
    options: BalanceOptions;
    canConfigure: boolean;
};

/**
 * What the studio noticed about the economy, worst first.
 *
 * These are the balance *interpretation* of evidence, not the evidence — Playtesting owns what happened at
 * the table. That is why the source reference is rendered as plain text rather than as a link: this module
 * does not resolve it, and an interface that linked it would be promising something no endpoint here can
 * deliver.
 *
 * The reference field only appears for the sources that point at something, which the server says with each
 * option — so a source added later behaves correctly without this file changing.
 */
export default function BalanceObservationList({
    observations,
    scope,
    options,
    canConfigure,
}: BalanceObservationListProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const form = useBalanceForm({
        initial: emptyObservationInput,
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: OBSERVATION_TITLE_MIN_LENGTH,
                    max: OBSERVATION_TITLE_MAX_LENGTH,
                    tooShort: t('Give the observation a title.'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
            observation:
                validateLength(input.observation, {
                    min: OBSERVATION_BODY_MIN_LENGTH,
                    max: OBSERVATION_BODY_MAX_LENGTH,
                    tooShort: t('Say what was actually seen.'),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createBalanceObservation(scope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    const expectsReference =
        options.observation_sources.find(
            (source) => source.value === form.input.source_type,
        )?.expects_reference ?? false;

    return (
        <div className="space-y-3">
            {observations.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                    data-test="observations-empty"
                >
                    {t(
                        'Nothing recorded yet. This is where what a playtest revealed about the numbers goes.',
                    )}
                </p>
            ) : (
                <ul className="space-y-2">
                    {observations.map((observation) => (
                        <li
                            key={observation.id}
                            className="rounded-md border p-3"
                            data-test={`observation-${observation.id}`}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <p className="min-w-0 font-medium" dir="auto">
                                    {observation.title}
                                </p>

                                <div className="flex items-center gap-2">
                                    <Badge variant="outline">
                                        {observation.source_type_label}
                                    </Badge>

                                    <ObservationSeverityBadge
                                        severity={observation.severity}
                                        label={observation.severity_label}
                                    />
                                </div>
                            </div>

                            <p className="mt-1 text-sm" dir="auto">
                                {observation.observation}
                            </p>

                            {observation.source_reference && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {t('Source')}{' '}
                                    <span dir="auto">
                                        {observation.source_reference}
                                    </span>
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {canConfigure && (
                <>
                    {adding ? (
                        <form
                            className="grid gap-3 rounded-md border p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.submit();
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="observation-title">
                                    {t('Title')}
                                </Label>

                                <Input
                                    id="observation-title"
                                    value={form.input.title}
                                    onChange={(event) =>
                                        form.setField(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t(
                                        'Wood becomes unlimited after round six',
                                    )}
                                    autoComplete="off"
                                    data-test="observation-title-input"
                                />

                                <InputError message={form.errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="observation-body">
                                    {t('What was seen?')}
                                </Label>

                                <Textarea
                                    id="observation-body"
                                    value={form.input.observation}
                                    onChange={(event) =>
                                        form.setField(
                                            'observation',
                                            event.target.value,
                                        )
                                    }
                                    rows={3}
                                    data-test="observation-body-input"
                                />

                                <InputError message={form.errors.observation} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="observation-source">
                                        {t('Where from?')}
                                    </Label>

                                    <Select
                                        value={form.input.source_type}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'source_type',
                                                value as ObservationSourceType,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="observation-source">
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.observation_sources.map(
                                                (source) => (
                                                    <SelectItem
                                                        key={source.value}
                                                        value={source.value}
                                                    >
                                                        {source.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="observation-severity">
                                        {t('How bad is it?')}
                                    </Label>

                                    <Select
                                        value={form.input.severity}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'severity',
                                                value as ObservationSeverity,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="observation-severity">
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.observation_severities.map(
                                                (severity) => (
                                                    <SelectItem
                                                        key={severity.value}
                                                        value={severity.value}
                                                    >
                                                        {severity.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            {expectsReference && (
                                <div className="grid gap-2">
                                    <Label htmlFor="observation-reference">
                                        {t('Reference')}{' '}
                                        <span className="font-normal text-muted-foreground">
                                            {t('(optional)')}
                                        </span>
                                    </Label>

                                    <Input
                                        id="observation-reference"
                                        value={form.input.source_reference}
                                        onChange={(event) =>
                                            form.setField(
                                                'source_reference',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t(
                                            'Which playtest or session?',
                                        )}
                                        autoComplete="off"
                                        dir="ltr"
                                    />
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test="submit-observation-button"
                                >
                                    {form.processing && <Spinner />}
                                    {t('Record observation')}
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
                            data-test="add-observation-button"
                        >
                            <Plus className="size-4" />
                            {t('Record observation')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
