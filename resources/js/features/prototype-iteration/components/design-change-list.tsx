import { PenLine, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { createDesignChange, deleteDesignChange } from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    CHANGE_REASON_MAX_LENGTH,
    CHANGE_REASON_MIN_LENGTH,
    CHANGE_TITLE_MAX_LENGTH,
    CHANGE_TITLE_MIN_LENGTH,
    emptyDesignChangeInput,
    validateLength,
} from '../schemas/prototype-iteration';
import type {
    ChangeCategory,
    DesignChange,
    IterationOptions,
} from '../types/prototype-iteration';

type DesignChangeListProps = {
    changes: DesignChange[];
    workspace: string;
    game: string;
    iteration: string;
    options: IterationOptions;
    canRecordWork: boolean;
};

/**
 * What the designer deliberately changed during this cycle.
 *
 * Every entry leads with its reason rather than its description, and the form asks for the reason before it
 * asks for anything optional. That ordering is the section's whole argument: a list of edits is a changelog
 * and answers "what is different"; a list of edits with reasons is a design rationale and answers "why is the
 * game like this", which is the question somebody actually has eighteen months later.
 *
 * The form is inline rather than in a dialog, because a designer back from a session records three or four of
 * these in a row and a dialog would mean opening and closing it each time.
 */
export default function DesignChangeList({
    changes,
    workspace,
    game,
    iteration,
    options,
    canRecordWork,
}: DesignChangeListProps) {
    const { t } = useTranslation();

    const form = useDesignForm({
        initial: emptyDesignChangeInput,
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: CHANGE_TITLE_MIN_LENGTH,
                    max: CHANGE_TITLE_MAX_LENGTH,
                    tooShort: t('Say what you changed.'),
                    tooLong: t('That title is too long.'),
                }) ?? undefined,
            reason:
                validateLength(input.reason, {
                    min: CHANGE_REASON_MIN_LENGTH,
                    max: CHANGE_REASON_MAX_LENGTH,
                    tooShort: t(
                        'Say why. This is the part nobody can reconstruct later.',
                    ),
                    tooLong: t('That reason is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createDesignChange({ workspace, game }, iteration, input, mutation),
    });

    return (
        <Card data-test="design-changes">
            <CardHeader>
                <CardTitle className="text-base">
                    {t('Design changes')}
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {changes.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-6 text-center text-sm text-muted-foreground"
                        data-test="changes-empty"
                    >
                        {t('Nothing changed yet.')}
                    </p>
                ) : (
                    <ol className="space-y-3" data-test="change-list">
                        {changes.map((change) => (
                            <li
                                key={change.id}
                                className="space-y-1 border-s ps-3"
                            >
                                <div className="flex flex-wrap items-start justify-between gap-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <PenLine className="size-3 text-muted-foreground" />
                                        <span
                                            className="text-sm font-medium"
                                            dir="auto"
                                        >
                                            {change.title}
                                        </span>
                                        <Badge variant="outline">
                                            {change.category_label}
                                        </Badge>
                                    </div>

                                    {canRecordWork && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                deleteDesignChange(
                                                    { workspace, game },
                                                    iteration,
                                                    change.id,
                                                )
                                            }
                                            aria-label={t('Remove change')}
                                            data-test={`delete-change-${change.id}`}
                                        >
                                            <Trash2 className="size-3.5" />
                                        </Button>
                                    )}
                                </div>

                                {change.description && (
                                    <p
                                        className="text-sm text-muted-foreground"
                                        dir="auto"
                                    >
                                        {change.description}
                                    </p>
                                )}

                                <p className="text-sm" dir="auto">
                                    <span className="font-medium text-muted-foreground">
                                        {t('Why:')}{' '}
                                    </span>
                                    {change.reason}
                                </p>
                            </li>
                        ))}
                    </ol>
                )}

                {canRecordWork && (
                    <form
                        className="grid gap-3 rounded-md border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit();
                        }}
                        data-test="create-change-form"
                    >
                        <div className="grid gap-2 sm:grid-cols-[10rem_1fr]">
                            <div className="grid gap-1">
                                <Label
                                    htmlFor="change-category"
                                    className="text-xs"
                                >
                                    {t('Category')}
                                </Label>

                                <Select
                                    value={form.input.category}
                                    onValueChange={(value) =>
                                        form.setField(
                                            'category',
                                            value as ChangeCategory,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="change-category"
                                        data-test="change-category-picker"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        {options.change_categories.map(
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

                            <div className="grid gap-1">
                                <Label
                                    htmlFor="change-title"
                                    className="text-xs"
                                >
                                    {t('What changed')}
                                </Label>

                                <Input
                                    id="change-title"
                                    value={form.input.title}
                                    onChange={(event) =>
                                        form.setField(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t('Remove reaction phase')}
                                    autoComplete="off"
                                    data-test="change-title-input"
                                />

                                <InputError message={form.errors.title} />
                            </div>
                        </div>

                        <div className="grid gap-1">
                            <Label htmlFor="change-reason" className="text-xs">
                                {t('Why you changed it')}
                            </Label>

                            <Textarea
                                id="change-reason"
                                value={form.input.reason}
                                onChange={(event) =>
                                    form.setField('reason', event.target.value)
                                }
                                placeholder={t(
                                    'Reaction windows created excessive downtime.',
                                )}
                                rows={2}
                                data-test="change-reason-input"
                            />

                            <InputError message={form.errors.reason} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="change-description"
                                className="text-xs"
                            >
                                {t('Detail')}{' '}
                                <span className="font-normal text-muted-foreground">
                                    {t('(optional)')}
                                </span>
                            </Label>

                            <Textarea
                                id="change-description"
                                value={form.input.description}
                                onChange={(event) =>
                                    form.setField(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    "Players no longer interrupt an opponent's action.",
                                )}
                                rows={2}
                            />
                        </div>

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                data-test="submit-change-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Record change')}
                            </Button>
                        </div>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}
