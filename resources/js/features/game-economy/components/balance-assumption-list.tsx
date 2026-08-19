import { HelpCircle, Plus } from 'lucide-react';
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
import { createBalanceAssumption, updateBalanceAssumption } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    ASSUMPTION_TITLE_MAX_LENGTH,
    ASSUMPTION_TITLE_MIN_LENGTH,
    emptyAssumptionInput,
    validateLength,
} from '../schemas/game-economy';
import type {
    AssumptionCategory,
    AssumptionConfidence,
    BalanceAssumption,
    BalanceOptions,
} from '../types/game-economy';

type BalanceAssumptionListProps = {
    assumptions: BalanceAssumption[];
    scope: ProfileScope;
    options: BalanceOptions;
    canConfigure: boolean;
};

/**
 * Why the numbers are what they are.
 *
 * Least-confident first, which is the opposite of how the observations beside it are ordered — and
 * deliberately so: an assumption list is read to find what has not been checked, an observation list to find
 * what is on fire.
 *
 * Confidence is editable in place, because raising it is the commonest thing that happens to an assumption.
 * A hunch that has since been demonstrated at four tables should be able to say so without somebody writing
 * the same sentence again more loudly.
 *
 * There is no delete. An assumption that turned out to be wrong is the most useful entry in the list — it is
 * the one that explains why the numbers changed.
 */
export default function BalanceAssumptionList({
    assumptions,
    scope,
    options,
    canConfigure,
}: BalanceAssumptionListProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);

    const form = useBalanceForm({
        initial: emptyAssumptionInput,
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: ASSUMPTION_TITLE_MIN_LENGTH,
                    max: ASSUMPTION_TITLE_MAX_LENGTH,
                    tooShort: t(
                        'Write the belief as a sentence somebody could test.',
                    ),
                    tooLong: t('That is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createBalanceAssumption(scope, input, mutation),
        onSuccess: () => setAdding(false),
    });

    return (
        <div className="space-y-3">
            {assumptions.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                    data-test="assumptions-empty"
                >
                    {t(
                        'No assumptions yet. Write down what the numbers are meant to achieve, so the next designer can tell what was measured from what was guessed.',
                    )}
                </p>
            ) : (
                <ul className="space-y-2">
                    {assumptions.map((assumption) => (
                        <li
                            key={assumption.id}
                            className="rounded-md border p-3"
                            data-test={`assumption-${assumption.id}`}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-2">
                                <p className="min-w-0 font-medium" dir="auto">
                                    {assumption.title}
                                </p>

                                <div className="flex items-center gap-2">
                                    <Badge variant="outline">
                                        {assumption.category_label}
                                    </Badge>

                                    {canConfigure ? (
                                        <Select
                                            value={assumption.confidence}
                                            onValueChange={(value) =>
                                                updateBalanceAssumption(
                                                    scope,
                                                    assumption.id,
                                                    {
                                                        confidence:
                                                            value as AssumptionConfidence,
                                                    },
                                                )
                                            }
                                        >
                                            <SelectTrigger
                                                className="h-8 w-44"
                                                aria-label={t('Confidence')}
                                                data-test={`assumption-confidence-${assumption.id}`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>

                                            <SelectContent>
                                                {options.confidences.map(
                                                    (confidence) => (
                                                        <SelectItem
                                                            key={
                                                                confidence.value
                                                            }
                                                            value={
                                                                confidence.value
                                                            }
                                                        >
                                                            {confidence.label}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Badge variant="secondary">
                                            {assumption.confidence_label}
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            {assumption.description && (
                                <p
                                    className="mt-1 text-sm text-muted-foreground"
                                    dir="auto"
                                >
                                    {assumption.description}
                                </p>
                            )}

                            {assumption.needs_evidence && (
                                <p className="mt-1 inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <HelpCircle className="size-3" />
                                    {t('Worth testing before building on it.')}
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
                                <Label htmlFor="assumption-title">
                                    {t('What do you believe?')}
                                </Label>

                                <Input
                                    id="assumption-title"
                                    value={form.input.title}
                                    onChange={(event) =>
                                        form.setField(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t(
                                        'Players should be able to afford one major action every round.',
                                    )}
                                    autoComplete="off"
                                    data-test="assumption-title-input"
                                />

                                <InputError message={form.errors.title} />
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="assumption-category">
                                        {t('Category')}
                                    </Label>

                                    <Select
                                        value={form.input.category}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'category',
                                                value as AssumptionCategory,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="assumption-category">
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.assumption_categories.map(
                                                (category) => (
                                                    <SelectItem
                                                        key={category.value}
                                                        value={category.value}
                                                    >
                                                        {category.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="assumption-confidence">
                                        {t('Confidence')}
                                    </Label>

                                    <Select
                                        value={form.input.confidence}
                                        onValueChange={(value) =>
                                            form.setField(
                                                'confidence',
                                                value as AssumptionConfidence,
                                            )
                                        }
                                    >
                                        <SelectTrigger id="assumption-confidence">
                                            <SelectValue />
                                        </SelectTrigger>

                                        <SelectContent>
                                            {options.confidences.map(
                                                (confidence) => (
                                                    <SelectItem
                                                        key={confidence.value}
                                                        value={confidence.value}
                                                    >
                                                        {confidence.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="assumption-description">
                                    {t('Why?')}{' '}
                                    <span className="font-normal text-muted-foreground">
                                        {t('(optional)')}
                                    </span>
                                </Label>

                                <Textarea
                                    id="assumption-description"
                                    value={form.input.description}
                                    onChange={(event) =>
                                        form.setField(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    rows={2}
                                />
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    disabled={form.processing}
                                    data-test="submit-assumption-button"
                                >
                                    {form.processing && <Spinner />}
                                    {t('Record assumption')}
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
                            data-test="add-assumption-button"
                        >
                            <Plus className="size-4" />
                            {t('Record assumption')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
