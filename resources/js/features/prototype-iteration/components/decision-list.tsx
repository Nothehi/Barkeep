import { useState } from 'react';
import InputError from '@/components/input-error';
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
import { useFormatters, useTranslation } from '@/lib/i18n';
import {
    acceptDecision,
    createDecision,
    createEvidence,
    deferDecision,
    rejectDecision,
} from '../api';
import { useDesignForm } from '../hooks/use-design-form';
import {
    DECISION_REASON_MAX_LENGTH,
    DECISION_REASON_MIN_LENGTH,
    DECISION_STATEMENT_MAX_LENGTH,
    DECISION_STATEMENT_MIN_LENGTH,
    DECISION_TITLE_MAX_LENGTH,
    DECISION_TITLE_MIN_LENGTH,
    emptyDecisionInput,
    emptyEvidenceInput,
    validateLength,
} from '../schemas/prototype-iteration';
import type {
    CitedEvidence,
    DesignDecision,
    EvidenceType,
    IterationOptions,
    PlaytestReference,
} from '../types/prototype-iteration';
import DecisionEvidence from './decision-evidence';
import { DecisionStatusBadge } from './status-badges';

type DecisionListProps = {
    decisions: DesignDecision[];
    evidence: Record<string, CitedEvidence[]>;
    playtests: PlaytestReference[];
    workspace: string;
    game: string;
    iteration: string;
    options: IterationOptions;
    canRecordWork: boolean;
};

/**
 * What this cycle concluded.
 *
 * The section the whole module builds towards. Each decision shows what was decided, why, where it stands and
 * what it cites — and the [Accept] [Reject] [Defer] buttons come from the server's own transition list, so a
 * settled decision has no buttons at all rather than having them hidden by a condition kept here.
 *
 * The note under the accept button is deliberate. Acceptance is terminal, and somebody about to press it
 * should know that a later change of mind is a new decision rather than an edit — otherwise the read-only
 * behaviour they meet afterwards reads as a bug.
 */
export default function DecisionList({
    decisions,
    evidence,
    playtests,
    workspace,
    game,
    iteration,
    options,
    canRecordWork,
}: DecisionListProps) {
    const { t } = useTranslation();

    const form = useDesignForm({
        initial: emptyDecisionInput,
        validate: (input) => ({
            title:
                validateLength(input.title, {
                    min: DECISION_TITLE_MIN_LENGTH,
                    max: DECISION_TITLE_MAX_LENGTH,
                    tooShort: t('Give the decision a title.'),
                    tooLong: t('That title is too long.'),
                }) ?? undefined,
            decision:
                validateLength(input.decision, {
                    min: DECISION_STATEMENT_MIN_LENGTH,
                    max: DECISION_STATEMENT_MAX_LENGTH,
                    tooShort: t('Say what you decided.'),
                    tooLong: t('That decision is too long.'),
                }) ?? undefined,
            reason:
                validateLength(input.reason, {
                    min: DECISION_REASON_MIN_LENGTH,
                    max: DECISION_REASON_MAX_LENGTH,
                    tooShort: t(
                        'Say why. Without it this is an instruction rather than an argument.',
                    ),
                    tooLong: t('That reason is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createDecision({ workspace, game }, iteration, input, mutation),
    });

    return (
        <Card data-test="decisions">
            <CardHeader>
                <CardTitle className="text-base">{t('Decisions')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-4">
                {decisions.length === 0 ? (
                    <p
                        className="rounded-md border border-dashed py-6 text-center text-sm text-muted-foreground"
                        data-test="decisions-empty"
                    >
                        {t('Nothing decided yet.')}
                    </p>
                ) : (
                    <ul className="space-y-3" data-test="decision-list">
                        {decisions.map((decision) => (
                            <DecisionEntry
                                key={decision.id}
                                decision={decision}
                                evidence={evidence[decision.id] ?? []}
                                playtests={playtests}
                                workspace={workspace}
                                game={game}
                                iteration={iteration}
                                options={options}
                                canRecordWork={canRecordWork}
                            />
                        ))}
                    </ul>
                )}

                {canRecordWork && (
                    <form
                        className="grid gap-3 rounded-md border p-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.submit();
                        }}
                        data-test="create-decision-form"
                    >
                        <div className="grid gap-1">
                            <Label htmlFor="decision-title" className="text-xs">
                                {t('Title')}
                            </Label>

                            <Input
                                id="decision-title"
                                value={form.input.title}
                                onChange={(event) =>
                                    form.setField('title', event.target.value)
                                }
                                placeholder={t('Reaction phase')}
                                autoComplete="off"
                                data-test="decision-title-input"
                            />

                            <InputError message={form.errors.title} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="decision-statement"
                                className="text-xs"
                            >
                                {t('What did you decide?')}
                            </Label>

                            <Textarea
                                id="decision-statement"
                                value={form.input.decision}
                                onChange={(event) =>
                                    form.setField(
                                        'decision',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    'Remove the reaction phase permanently.',
                                )}
                                rows={2}
                                data-test="decision-statement-input"
                            />

                            <InputError message={form.errors.decision} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor="decision-reason"
                                className="text-xs"
                            >
                                {t('Why?')}
                            </Label>

                            <Textarea
                                id="decision-reason"
                                value={form.input.reason}
                                onChange={(event) =>
                                    form.setField('reason', event.target.value)
                                }
                                placeholder={t(
                                    'Players made decisions faster and average downtime fell by about a fifth.',
                                )}
                                rows={2}
                                data-test="decision-reason-input"
                            />

                            <InputError message={form.errors.reason} />
                        </div>

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                data-test="submit-decision-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Propose decision')}
                            </Button>
                        </div>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * One decision, what it cites, and what can still be done to it.
 */
function DecisionEntry({
    decision,
    evidence,
    playtests,
    workspace,
    game,
    iteration,
    options,
    canRecordWork,
}: {
    decision: DesignDecision;
    evidence: CitedEvidence[];
    playtests: PlaytestReference[];
    workspace: string;
    game: string;
    iteration: string;
    options: IterationOptions;
    canRecordWork: boolean;
}) {
    const { t } = useTranslation();
    const { formatDate } = useFormatters();
    const [citing, setCiting] = useState(false);

    const offers = (status: string) =>
        decision.available_transitions.some((move) => move.status === status);

    const citation = useDesignForm({
        initial: emptyEvidenceInput,
        perform: (input, mutation) =>
            createEvidence(
                { workspace, game },
                iteration,
                decision.id,
                input,
                mutation,
            ),
        onSuccess: () => setCiting(false),
    });

    const selectedType = options.evidence_types.find(
        (type) => type.value === citation.input.type,
    );

    return (
        <li
            className="space-y-2 rounded-md border p-3"
            data-test={`decision-${decision.id}`}
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <span className="text-sm font-medium" dir="auto">
                    {decision.title}
                </span>

                <DecisionStatusBadge
                    status={decision.status}
                    label={decision.status_label}
                />
            </div>

            <p className="text-sm" dir="auto">
                {decision.decision}
            </p>

            <p className="text-sm text-muted-foreground" dir="auto">
                <span className="font-medium">{t('Why:')} </span>
                {decision.reason}
            </p>

            {decision.decided_at && decision.decider && (
                <p className="text-xs text-muted-foreground">
                    {t('Settled by :name on :date', {
                        name: decision.decider.name,
                        date: formatDate(decision.decided_at),
                    })}
                </p>
            )}

            <div className="space-y-2 border-t pt-2">
                <p className="text-xs font-medium text-muted-foreground">
                    {t('Evidence')}
                </p>
                <DecisionEvidence
                    evidence={evidence}
                    workspace={workspace}
                    game={game}
                />
            </div>

            {canRecordWork && !decision.is_settled && (
                <div className="flex flex-wrap items-center gap-2 pt-1">
                    {offers('accepted') && (
                        <Button
                            size="sm"
                            onClick={() =>
                                acceptDecision(
                                    { workspace, game },
                                    iteration,
                                    decision.id,
                                )
                            }
                            data-test={`accept-decision-${decision.id}`}
                        >
                            {t('Accept')}
                        </Button>
                    )}

                    {offers('rejected') && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                rejectDecision(
                                    { workspace, game },
                                    iteration,
                                    decision.id,
                                )
                            }
                            data-test={`reject-decision-${decision.id}`}
                        >
                            {t('Reject')}
                        </Button>
                    )}

                    {offers('deferred') && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                deferDecision(
                                    { workspace, game },
                                    iteration,
                                    decision.id,
                                )
                            }
                            data-test={`defer-decision-${decision.id}`}
                        >
                            {t('Defer')}
                        </Button>
                    )}

                    {!citing && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setCiting(true)}
                            data-test={`cite-evidence-${decision.id}`}
                        >
                            {t('Cite evidence')}
                        </Button>
                    )}
                </div>
            )}

            {canRecordWork && !decision.is_settled && (
                <p className="text-xs text-muted-foreground">
                    {t(
                        'Accepting or rejecting settles this for good. Record a new decision in a later iteration if it changes.',
                    )}
                </p>
            )}

            {citing && (
                <form
                    className="grid gap-2 border-t pt-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        citation.submit();
                    }}
                    data-test={`cite-evidence-form-${decision.id}`}
                >
                    <Label
                        htmlFor={`evidence-type-${decision.id}`}
                        className="text-xs"
                    >
                        {t('What are you citing?')}
                    </Label>

                    <Select
                        value={citation.input.type}
                        onValueChange={(value) => {
                            citation.setField('type', value as EvidenceType);
                            citation.setField('reference_id', '');
                        }}
                    >
                        <SelectTrigger
                            id={`evidence-type-${decision.id}`}
                            data-test={`evidence-type-picker-${decision.id}`}
                        >
                            <SelectValue />
                        </SelectTrigger>

                        <SelectContent>
                            {options.evidence_types.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    {/*
                     * Only playtests get a picker. Observations and feedback are cited from the playtest
                     * screen, where somebody is looking at the actual words — choosing one by uuid from a
                     * dropdown here would be guessing.
                     */}
                    {citation.input.type === 'playtest' && (
                        <>
                            <Label
                                htmlFor={`evidence-ref-${decision.id}`}
                                className="text-xs"
                            >
                                {t('Which playtest?')}
                            </Label>

                            <Select
                                value={citation.input.reference_id}
                                onValueChange={(value) =>
                                    citation.setField('reference_id', value)
                                }
                            >
                                <SelectTrigger
                                    id={`evidence-ref-${decision.id}`}
                                    data-test={`evidence-ref-picker-${decision.id}`}
                                >
                                    <SelectValue
                                        placeholder={t('Choose a playtest')}
                                    />
                                </SelectTrigger>

                                <SelectContent>
                                    {playtests.map((playtest) => (
                                        <SelectItem
                                            key={playtest.playtest_id}
                                            value={playtest.playtest_id}
                                        >
                                            {playtest.title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </>
                    )}

                    {selectedType?.requires_reference &&
                        citation.input.type !== 'playtest' && (
                            <>
                                <Label
                                    htmlFor={`evidence-ref-${decision.id}`}
                                    className="text-xs"
                                >
                                    {t('Reference')}
                                </Label>

                                <Input
                                    id={`evidence-ref-${decision.id}`}
                                    value={citation.input.reference_id}
                                    onChange={(event) =>
                                        citation.setField(
                                            'reference_id',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t(
                                        'Paste the identifier from the playtest screen',
                                    )}
                                    autoComplete="off"
                                    dir="ltr"
                                />
                            </>
                        )}

                    <InputError message={citation.errors.reference_id} />

                    <Label
                        htmlFor={`evidence-note-${decision.id}`}
                        className="text-xs"
                    >
                        {selectedType?.requires_reference
                            ? t('Why does it support this?')
                            : t('What did you want to note?')}
                    </Label>

                    <Textarea
                        id={`evidence-note-${decision.id}`}
                        value={citation.input.description}
                        onChange={(event) =>
                            citation.setField('description', event.target.value)
                        }
                        rows={2}
                        data-test={`evidence-description-${decision.id}`}
                    />

                    <InputError message={citation.errors.description} />
                    <InputError message={citation.errors.type} />

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setCiting(false)}
                        >
                            {t('Cancel')}
                        </Button>

                        <Button
                            type="submit"
                            size="sm"
                            disabled={citation.processing}
                            data-test={`submit-evidence-${decision.id}`}
                        >
                            {citation.processing && <Spinner />}
                            {t('Cite evidence')}
                        </Button>
                    </div>
                </form>
            )}
        </li>
    );
}
