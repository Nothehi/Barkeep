import { Eye, Plus, X } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { useObservations } from '../hooks/use-observations';
import type {
    Observation,
    ObservationCategory,
    Participant,
    PlaytestOptions,
    PlaytestSession,
} from '../types/playtest';

type ObservationListProps = {
    session: PlaytestSession;
    observations: Observation[];
    participants: Participant[];
    options: PlaytestOptions;
    workspace: string;
    game: string;
    playtest: string;
};

const NOBODY = 'nobody';

/**
 * What the designers noticed, and the form that records another.
 *
 * The textarea is the whole design. Everything else on this form has a
 * default, so recording an observation is: type a sentence, press enter.
 * Anything that makes that slower means fewer observations get recorded, and
 * unrecorded observations are the ones nobody remembers by the time they get
 * home.
 *
 * ⌘/Ctrl+Enter submits, because the field has to accept newlines and somebody
 * mid-session should not have to reach for a mouse.
 *
 * The category persists between submissions and the text does not: a designer
 * filing four rules problems in a row should not re-pick "rules" each time.
 */
export default function ObservationList({
    session,
    observations,
    participants,
    options,
    workspace,
    game,
    playtest,
}: ObservationListProps) {
    const { t } = useTranslation();
    const form = useObservations(
        workspace,
        game,
        playtest,
        session.id,
        observations,
    );

    const canAdd = session.permissions.canCreateObservation;
    const canManage = session.permissions.canManageObservations;

    return (
        <section className="space-y-3" data-test="observation-list">
            <h2 className="font-semibold">
                {t('Observations')}{' '}
                <span className="text-sm font-normal text-muted-foreground">
                    {t('what you noticed')}
                </span>
            </h2>

            {canAdd && (
                <form
                    className="space-y-3 rounded-lg border p-3"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit();
                    }}
                >
                    <Textarea
                        value={form.input.content}
                        onChange={(event) =>
                            form.setField('content', event.target.value)
                        }
                        onKeyDown={(event) => {
                            if (
                                event.key === 'Enter' &&
                                (event.metaKey || event.ctrlKey)
                            ) {
                                event.preventDefault();
                                form.submit();
                            }
                        }}
                        placeholder={t(
                            'Player misunderstood the scoring rule.',
                        )}
                        rows={2}
                        aria-label={t('What did you notice?')}
                        data-test="observation-input"
                    />

                    <InputError message={form.errors.content} />

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="grid gap-1.5">
                            <Label
                                htmlFor="observation-category"
                                className="text-xs"
                            >
                                {t('Category')}
                            </Label>

                            <Select
                                value={form.input.category}
                                onValueChange={(value) =>
                                    form.setField(
                                        'category',
                                        value as ObservationCategory,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="observation-category"
                                    className="w-44"
                                    data-test="observation-category-picker"
                                >
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    {options.categories.map((category) => (
                                        <SelectItem
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {participants.length > 0 && (
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="observation-participant"
                                    className="text-xs"
                                >
                                    {t('About')}
                                </Label>

                                <Select
                                    value={form.input.participant_id || NOBODY}
                                    onValueChange={(value) =>
                                        form.setField(
                                            'participant_id',
                                            value === NOBODY ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="observation-participant"
                                        className="w-44"
                                        data-test="observation-participant-picker"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem value={NOBODY}>
                                            {t('The table')}
                                        </SelectItem>

                                        {participants.map((participant) => (
                                            <SelectItem
                                                key={participant.id}
                                                value={participant.id}
                                                dir="auto"
                                            >
                                                {participant.display_name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="ms-auto"
                            data-test="add-observation-button"
                        >
                            {form.processing ? (
                                <Spinner />
                            ) : (
                                <Plus className="size-4" />
                            )}
                            {t('Record')}
                        </Button>
                    </div>
                </form>
            )}

            {observations.length === 0 ? (
                <p
                    className="flex flex-col items-center gap-2 rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                    data-test="observations-empty"
                >
                    <Eye className="size-5" />
                    {t('Nothing noticed yet.')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {observations.map((observation) => (
                        <li
                            key={observation.id}
                            className="flex items-start justify-between gap-3 rounded-lg border p-3"
                        >
                            <div className="min-w-0 space-y-1">
                                <p className="text-sm" dir="auto">
                                    {observation.content}
                                </p>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="outline">
                                        {observation.category_label}
                                    </Badge>

                                    {observation.participant && (
                                        <span className="text-xs text-muted-foreground">
                                            {t('about :name', {
                                                name: observation.participant
                                                    .display_name,
                                            })}
                                        </span>
                                    )}
                                </div>
                            </div>

                            {canManage && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Remove observation')}
                                    onClick={() => form.remove(observation.id)}
                                    data-test={`remove-observation-${observation.id}`}
                                >
                                    <X className="size-4" />
                                </Button>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
