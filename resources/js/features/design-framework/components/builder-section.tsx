import { ChevronDown, ChevronUp, Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
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
import type { ContentInput, ContentType } from '../api';
import { createContent, reorderContent } from '../api';
import type { DesignPhase } from '../types/framework';
import FrameworkStatusBadge from './framework-status-badge';

/**
 * One row in a builder section, reduced to what the section actually draws.
 *
 * A structural type rather than a union of the six content types, because a
 * section is the same list whichever kind it holds — and because the fields it
 * needs are exactly the fields all six share.
 */
export type BuilderRow = {
    id: string;
    label: string;
    detail: string | null;
    position: number;
    phase_id: string | null;
    status: 'draft' | 'published' | 'archived';
    status_label: string;
};

type BuilderSectionProps = {
    framework: string;
    version: number;
    type: ContentType;
    heading: string;
    description: string;
    rows: BuilderRow[];
    phases: DesignPhase[];
    editable: boolean;

    /**
     * The name of the field this kind is written from — `name` for a phase,
     * `title` for everything else — plus its longer companion.
     */
    fields: {
        primary: 'name' | 'title';
        secondary?: 'description' | 'prompt' | 'instructions';
        secondaryLabel?: string;
    };

    /**
     * Whether the kind can be filed under a phase. Phases themselves cannot,
     * because they *are* the arc.
     */
    filed?: boolean;
};

/**
 * One kind of content inside an edition, listed and — while the edition is a
 * draft — extended.
 *
 * The six kinds share one component because the server exposes them through
 * one shape: create, update and reorder on the same URL, filed under a phase
 * or under none. Six near-identical components would be six places for the
 * ordering rules to drift apart.
 *
 * Nothing is editable once the edition is published, and the section does not
 * dress that up as a permission problem — a frozen edition is the point of
 * versioning, and the reason a game on v1 keeps reading the same questions.
 *
 * Reordering is up and down buttons rather than a drag. A drag needs a pointer
 * and a steady hand, and the underlying operation is "put this at position N"
 * either way — the server rewrites the whole list, so every move lands
 * contiguous.
 */
export default function BuilderSection({
    framework,
    version,
    type,
    heading,
    description,
    rows,
    phases,
    editable,
    fields,
    filed = true,
}: BuilderSectionProps) {
    const { t } = useTranslation();
    const [adding, setAdding] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [moving, setMoving] = useState<string | null>(null);
    const [primary, setPrimary] = useState('');
    const [secondary, setSecondary] = useState('');
    const [phaseId, setPhaseId] = useState<string>(UNFILED);

    const move = (row: BuilderRow, delta: number) => {
        setMoving(row.id);

        reorderContent(framework, version, type, row.id, row.position + delta, {
            onFinish: () => setMoving(null),
        });
    };

    const submit = () => {
        setProcessing(true);
        setErrors({});

        const input: ContentInput = { [fields.primary]: primary };

        if (fields.secondary) {
            input[fields.secondary] = secondary.trim() || null;
        }

        if (filed) {
            input.phase_id = phaseId === UNFILED ? null : phaseId;
        }

        createContent(framework, version, type, input, {
            onSuccess: () => {
                setAdding(false);
                setPrimary('');
                setSecondary('');
                setPhaseId(UNFILED);
            },
            onError: setErrors,
            onFinish: () => setProcessing(false),
        });
    };

    /**
     * Content is grouped by phase for display, and the phase-less group comes
     * first — it applies across the whole methodology and reads as a preamble
     * to the stages that follow. The server already sends the list in that
     * order, so this only has to find the boundaries.
     *
     * Phases themselves are one flat list. They are the thing everything else
     * is filed under, so grouping them by phase would be grouping them by
     * themselves.
     */
    const groups = filed
        ? groupByPhase(rows, phases, t)
        : [{ key: 'all', label: null, rows }];

    return (
        <section className="space-y-3" data-test={`builder-${type}`}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                    <h2 className="text-sm font-medium">{heading}</h2>

                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                </div>

                {editable && !adding && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => setAdding(true)}
                        data-test={`add-${type}`}
                    >
                        <Plus className="size-4" />
                        {t('Add')}
                    </Button>
                )}
            </div>

            {adding && (
                <Card>
                    <CardContent className="grid gap-3 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor={`${type}-primary`}>
                                {fields.primary === 'name'
                                    ? t('Name')
                                    : t('Title')}
                            </Label>

                            <Input
                                id={`${type}-primary`}
                                value={primary}
                                onChange={(event) =>
                                    setPrimary(event.target.value)
                                }
                                autoFocus
                                data-test={`${type}-primary-input`}
                            />

                            <InputError message={errors[fields.primary]} />
                        </div>

                        {fields.secondary && (
                            <div className="grid gap-2">
                                <Label htmlFor={`${type}-secondary`}>
                                    {fields.secondaryLabel ?? t('Description')}
                                </Label>

                                <Textarea
                                    id={`${type}-secondary`}
                                    value={secondary}
                                    onChange={(event) =>
                                        setSecondary(event.target.value)
                                    }
                                    rows={3}
                                    data-test={`${type}-secondary-input`}
                                />

                                <InputError
                                    message={errors[fields.secondary]}
                                />
                            </div>
                        )}

                        {filed && phases.length > 0 && (
                            <div className="grid gap-2">
                                <Label>{t('Phase')}</Label>

                                <Select
                                    value={phaseId}
                                    onValueChange={setPhaseId}
                                >
                                    <SelectTrigger
                                        data-test={`${type}-phase-select`}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem value={UNFILED}>
                                            {t('No phase — applies throughout')}
                                        </SelectItem>

                                        {phases.map((phase) => (
                                            <SelectItem
                                                key={phase.id}
                                                value={phase.id}
                                                dir="auto"
                                            >
                                                {phase.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.phase_id} />
                            </div>
                        )}

                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                disabled={processing || primary.trim() === ''}
                                onClick={submit}
                                data-test={`${type}-submit`}
                            >
                                {processing && <Spinner />}
                                {t('Add')}
                            </Button>

                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    setAdding(false);
                                    setErrors({});
                                }}
                            >
                                {t('Cancel')}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {rows.length === 0 ? (
                <p className="rounded-lg border border-dashed px-4 py-6 text-center text-sm text-muted-foreground">
                    {t('Nothing here yet.')}
                </p>
            ) : (
                <div className="grid gap-4">
                    {groups.map((group) => (
                        <div key={group.key} className="space-y-2">
                            {group.label !== null && (
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {group.label}
                                </p>
                            )}

                            {group.rows.map((row, index) => (
                                <Card
                                    key={row.id}
                                    data-test={`${type}-row-${row.id}`}
                                >
                                    <CardHeader className="gap-1">
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <span className="min-w-0 font-medium">
                                                {row.label}
                                            </span>

                                            <div className="flex shrink-0 items-center gap-2">
                                                {row.status !== 'published' && (
                                                    <FrameworkStatusBadge
                                                        status={row.status}
                                                        label={row.status_label}
                                                    />
                                                )}

                                                {editable && (
                                                    <>
                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="size-7"
                                                            disabled={
                                                                index === 0 ||
                                                                moving !== null
                                                            }
                                                            onClick={() =>
                                                                move(row, -1)
                                                            }
                                                            aria-label={t(
                                                                'Move :label up',
                                                                {
                                                                    label: row.label,
                                                                },
                                                            )}
                                                            data-test={`${type}-up-${row.id}`}
                                                        >
                                                            <ChevronUp className="size-4" />
                                                        </Button>

                                                        <Button
                                                            size="icon"
                                                            variant="ghost"
                                                            className="size-7"
                                                            disabled={
                                                                index ===
                                                                    group.rows
                                                                        .length -
                                                                        1 ||
                                                                moving !== null
                                                            }
                                                            onClick={() =>
                                                                move(row, 1)
                                                            }
                                                            aria-label={t(
                                                                'Move :label down',
                                                                {
                                                                    label: row.label,
                                                                },
                                                            )}
                                                            data-test={`${type}-down-${row.id}`}
                                                        >
                                                            <ChevronDown className="size-4" />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </div>

                                        {row.detail && (
                                            <span className="text-sm text-muted-foreground">
                                                {row.detail}
                                            </span>
                                        )}
                                    </CardHeader>
                                </Card>
                            ))}
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}

/**
 * The sentinel the phase picker uses for "no phase".
 *
 * A Radix select cannot hold an empty string as a value, so the absence needs
 * a name; it is translated back to null before it reaches the server.
 */
const UNFILED = 'no-phase';

type Group = {
    key: string;

    /** Absent when the list is not grouped at all. */
    label: string | null;

    rows: BuilderRow[];
};

/**
 * Split a flat, already-ordered list into its phase groups.
 */
function groupByPhase(
    rows: BuilderRow[],
    phases: DesignPhase[],
    t: (phrase: string) => string,
): Group[] {
    const names = new Map(phases.map((phase) => [phase.id, phase.name]));
    const groups: Group[] = [];

    for (const row of rows) {
        const key = row.phase_id ?? UNFILED;
        const last = groups.at(-1);

        if (last?.key === key) {
            last.rows.push(row);
            continue;
        }

        groups.push({
            key,
            label:
                row.phase_id === null
                    ? t('Applies throughout')
                    : (names.get(row.phase_id) ?? t('Unknown phase')),
            rows: [row],
        });
    }

    return groups;
}
