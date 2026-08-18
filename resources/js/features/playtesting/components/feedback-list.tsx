import { MessageSquare, Plus, Star, X } from 'lucide-react';
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
import { useFormatters, useTranslation } from '@/lib/i18n';
import { useFeedback } from '../hooks/use-feedback';
import type {
    Feedback,
    Participant,
    PlaytestOptions,
    PlaytestSession,
} from '../types/playtest';

type FeedbackListProps = {
    session: PlaytestSession;
    feedback: Feedback[];
    participants: Participant[];
    options: PlaytestOptions;
    workspace: string;
    game: string;
    playtest: string;
};

const ANONYMOUS = 'anonymous';
const NO_RATING = 'none';

/**
 * What the participants said, and the form that records it.
 *
 * Drawn separately from the observations rather than mixed in with them, and
 * that separation is the point of the whole screen. "The scoring confused
 * them" is a designer's reading; "I didn't understand the scoring" is a
 * player's own words, and the second is worth more precisely because nobody
 * interpreted it first.
 *
 * Both the speaker and the score are optional. Anonymous feedback is often the
 * honest kind, and a comment without a number is still feedback — requiring
 * either would lose exactly the material that is hardest to collect.
 */
export default function FeedbackList({
    session,
    feedback,
    participants,
    options,
    workspace,
    game,
    playtest,
}: FeedbackListProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();
    const form = useFeedback(workspace, game, playtest, session.id, feedback);

    const canAdd = session.permissions.canCreateFeedback;
    const canManage = session.permissions.canManageFeedback;

    return (
        <section className="space-y-3" data-test="feedback-list">
            <h2 className="font-semibold">
                {t('Feedback')}{' '}
                <span className="text-sm font-normal text-muted-foreground">
                    {t('what they told you')}
                    {form.averageRating !== null &&
                        ` · ${t(':rating average', {
                            rating: formatNumber(
                                Number(form.averageRating.toFixed(1)),
                            ),
                        })}`}
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
                        placeholder={t("I didn't know what my best move was.")}
                        rows={2}
                        aria-label={t('What did they say?')}
                        data-test="feedback-input"
                    />

                    <InputError message={form.errors.content} />

                    <div className="flex flex-wrap items-end gap-3">
                        {participants.length > 0 && (
                            <div className="grid gap-1.5">
                                <Label
                                    htmlFor="feedback-participant"
                                    className="text-xs"
                                >
                                    {t('From')}
                                </Label>

                                <Select
                                    value={
                                        form.input.participant_id || ANONYMOUS
                                    }
                                    onValueChange={(value) =>
                                        form.setField(
                                            'participant_id',
                                            value === ANONYMOUS ? '' : value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="feedback-participant"
                                        className="w-44"
                                        data-test="feedback-participant-picker"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem value={ANONYMOUS}>
                                            {t('Anonymous')}
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

                        <div className="grid gap-1.5">
                            <Label
                                htmlFor="feedback-rating"
                                className="text-xs"
                            >
                                {t('Rating')}
                            </Label>

                            <Select
                                value={form.input.rating || NO_RATING}
                                onValueChange={(value) =>
                                    form.setField(
                                        'rating',
                                        value === NO_RATING ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger
                                    id="feedback-rating"
                                    className="w-36"
                                    data-test="feedback-rating-picker"
                                >
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value={NO_RATING}>
                                        {t('No rating')}
                                    </SelectItem>

                                    {options.rating_scale.map((point) => (
                                        <SelectItem
                                            key={point}
                                            value={point.toString()}
                                        >
                                            {point} /{' '}
                                            {
                                                options.rating_scale[
                                                    options.rating_scale
                                                        .length - 1
                                                ]
                                            }
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Button
                            type="submit"
                            disabled={form.processing}
                            className="ms-auto"
                            data-test="add-feedback-button"
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

            {feedback.length === 0 ? (
                <p
                    className="flex flex-col items-center gap-2 rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                    data-test="feedback-empty"
                >
                    <MessageSquare className="size-5" />
                    {t('Nobody has said anything yet.')}
                </p>
            ) : (
                <ul className="space-y-2">
                    {feedback.map((item) => (
                        <li
                            key={item.id}
                            className="flex items-start justify-between gap-3 rounded-lg border p-3"
                        >
                            <div className="min-w-0 space-y-1">
                                <p className="text-sm" dir="auto">
                                    “{item.content}”
                                </p>

                                <div className="flex flex-wrap items-center gap-2">
                                    <span
                                        className="text-xs text-muted-foreground"
                                        dir="auto"
                                    >
                                        {item.participant?.display_name ??
                                            t('Anonymous')}
                                    </span>

                                    {item.rating_label && (
                                        <Badge variant="outline">
                                            <Star className="size-3" />
                                            {item.rating_label}
                                        </Badge>
                                    )}
                                </div>
                            </div>

                            {canManage && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Remove feedback')}
                                    onClick={() => form.remove(item.id)}
                                    data-test={`remove-feedback-${item.id}`}
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
