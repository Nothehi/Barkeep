import Heading from '@/components/heading';
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
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { useUpdateDesignRecord } from '../hooks/use-update-design-record';
import type {
    Complexity,
    ComplexityOptions,
    DesignRecord,
} from '../types/design-record';
import type { Game } from '../types/game';
import type { Mechanic } from '../types/mechanic';
import MechanicsPicker from './mechanics-picker';

type DesignRecordFormProps = {
    workspace: string;
    game: Game;
    record: DesignRecord | null;
    mechanics: Mechanic[];
    options: ComplexityOptions;
    canEdit: boolean;
};

/**
 * What has been decided about the game's design.
 *
 * This is the answer to a complaint about the framework screens: a designer used
 * to grade themselves against "are the player count and playing time decided?"
 * and tick "player count decided" on their own word. Here they write down two to
 * four, and the factual criteria answer themselves from it.
 *
 * Every field is optional, and the form says so rather than implying it. Deciding
 * is the work — a game in ideation has answered none of this, and a required
 * field would be the tool insisting on an answer before there is one. An empty
 * box means "not yet", which is a state a methodology reads and reports rather
 * than one it hides.
 *
 * The sections follow the order a design gets made in: what it is, who it is
 * for, and then the loop it is built from. That is also the order the seeded
 * framework asks the questions in, which is not a coincidence — the fields exist
 * because it asks.
 */
export default function DesignRecordForm({
    workspace,
    game,
    record,
    mechanics,
    options,
    canEdit,
}: DesignRecordFormProps) {
    const { t } = useTranslation();
    const form = useUpdateDesignRecord(workspace, game.slug, record);

    /**
     * The Radix select cannot hold an empty string as a value, so "not decided"
     * needs a name of its own; it is translated back to an empty field before
     * the request is built.
     */
    const UNDECIDED = 'undecided';

    const complexities = [...options.complexities].sort(
        (a, b) => a.position - b.position,
    );

    return (
        <form
            className="space-y-8"
            onSubmit={(event) => {
                event.preventDefault();
                form.submit();
            }}
        >
            <div className="space-y-6">
                <div className="grid gap-2">
                    <Label htmlFor="pitch">{t('One-sentence pitch')}</Label>

                    <Textarea
                        id="pitch"
                        value={form.input.pitch}
                        onChange={(event) =>
                            form.setField('pitch', event.target.value)
                        }
                        disabled={!canEdit}
                        rows={2}
                        placeholder={t(
                            'A game about ___ where players ___ in order to ___.',
                        )}
                        data-test="pitch-input"
                    />

                    <p className="text-sm text-muted-foreground">
                        {t(
                            'Not the theme and not the mechanisms — the experience. If it takes a paragraph, the idea is still several ideas.',
                        )}
                    </p>

                    <InputError message={form.errors.pitch} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="audience">{t('Intended audience')}</Label>

                    <Input
                        id="audience"
                        value={form.input.audience}
                        onChange={(event) =>
                            form.setField('audience', event.target.value)
                        }
                        disabled={!canEdit}
                        placeholder={t(
                            'Families who already play a few games a year',
                        )}
                        data-test="audience-input"
                    />

                    <InputError message={form.errors.audience} />
                </div>
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Constraints')}
                    description={t(
                        'These decide which mechanisms are even available, so they are design rather than paperwork',
                    )}
                />

                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="player_count_min">
                            {t('Players, from')}
                        </Label>

                        <Input
                            id="player_count_min"
                            type="number"
                            inputMode="numeric"
                            value={form.input.player_count_min}
                            onChange={(event) =>
                                form.setField(
                                    'player_count_min',
                                    event.target.value,
                                )
                            }
                            disabled={!canEdit}
                            data-test="player-count-min-input"
                        />

                        <InputError message={form.errors.player_count_min} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="player_count_max">{t('to')}</Label>

                        <Input
                            id="player_count_max"
                            type="number"
                            inputMode="numeric"
                            value={form.input.player_count_max}
                            onChange={(event) =>
                                form.setField(
                                    'player_count_max',
                                    event.target.value,
                                )
                            }
                            disabled={!canEdit}
                            placeholder={t('Leave blank for a fixed count')}
                            data-test="player-count-max-input"
                        />

                        <InputError message={form.errors.player_count_max} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="play_time_min">
                            {t('Minutes, from')}
                        </Label>

                        <Input
                            id="play_time_min"
                            type="number"
                            inputMode="numeric"
                            value={form.input.play_time_min}
                            onChange={(event) =>
                                form.setField(
                                    'play_time_min',
                                    event.target.value,
                                )
                            }
                            disabled={!canEdit}
                            data-test="play-time-min-input"
                        />

                        <InputError message={form.errors.play_time_min} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="play_time_max">{t('to')}</Label>

                        <Input
                            id="play_time_max"
                            type="number"
                            inputMode="numeric"
                            value={form.input.play_time_max}
                            onChange={(event) =>
                                form.setField(
                                    'play_time_max',
                                    event.target.value,
                                )
                            }
                            disabled={!canEdit}
                            data-test="play-time-max-input"
                        />

                        <InputError message={form.errors.play_time_max} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="target_age_min">
                            {t('Youngest player')}
                        </Label>

                        <Input
                            id="target_age_min"
                            type="number"
                            inputMode="numeric"
                            value={form.input.target_age_min}
                            onChange={(event) =>
                                form.setField(
                                    'target_age_min',
                                    event.target.value,
                                )
                            }
                            disabled={!canEdit}
                            data-test="target-age-input"
                        />

                        <InputError message={form.errors.target_age_min} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="complexity">{t('Weight')}</Label>

                        <Select
                            value={form.input.complexity || UNDECIDED}
                            onValueChange={(value) =>
                                form.setField(
                                    'complexity',
                                    value === UNDECIDED
                                        ? ''
                                        : (value as Complexity),
                                )
                            }
                            disabled={!canEdit}
                        >
                            <SelectTrigger
                                id="complexity"
                                data-test="complexity-select"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem value={UNDECIDED}>
                                    {t('Not decided')}
                                </SelectItem>

                                {complexities.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={form.errors.complexity} />
                    </div>
                </div>

                {/*
                 * The chosen weight's description is shown rather than tucked
                 * into a tooltip, because the difference between "gateway" and
                 * "hobby" is the whole decision and a designer guessing at it
                 * produces a number nobody can use.
                 */}
                {form.input.complexity !== '' && (
                    <p
                        className="text-sm text-muted-foreground"
                        data-test="complexity-description"
                        dir="auto"
                    >
                        {
                            complexities.find(
                                (option) =>
                                    option.value === form.input.complexity,
                            )?.description
                        }
                    </p>
                )}
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('Mechanics')}
                    description={t(
                        "Claimed from the platform's shared vocabulary, so two games that work the same way say so the same way",
                    )}
                />

                <MechanicsPicker
                    mechanics={mechanics}
                    claimed={form.input.mechanics}
                    onChange={(ids) => form.setField('mechanics', ids)}
                    disabled={!canEdit}
                />

                <InputError message={form.errors.mechanics} />
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('The core loop')}
                    description={t(
                        'The repeating action, consequence and reward the whole game is made of',
                    )}
                />

                {(
                    [
                        [
                            'core_action',
                            t('What the player does'),
                            t('Place a worker on an action space.'),
                        ],
                        [
                            'core_cost',
                            t('What it costs'),
                            t(
                                'The worker is unavailable until the round ends.',
                            ),
                        ],
                        [
                            'core_reward',
                            t('What it gives back'),
                            t('The space pays out its resource.'),
                        ],
                        [
                            'win_condition',
                            t('How the game is won'),
                            t('Most points when the last space is claimed.'),
                        ],
                        [
                            'failure_condition',
                            t('How a player loses ground'),
                            t(
                                'A player who cannot place a worker sits the round out.',
                            ),
                        ],
                    ] as const
                ).map(([field, label, placeholder]) => (
                    <div key={field} className="grid gap-2">
                        <Label htmlFor={field}>{label}</Label>

                        <Textarea
                            id={field}
                            value={form.input[field]}
                            onChange={(event) =>
                                form.setField(field, event.target.value)
                            }
                            disabled={!canEdit}
                            rows={2}
                            placeholder={placeholder}
                            data-test={`${field}-input`}
                        />

                        <InputError message={form.errors[field]} />
                    </div>
                ))}
            </div>

            {canEdit && (
                <Button
                    type="submit"
                    disabled={form.processing || !form.isDirty || !form.isValid}
                    data-test="save-design-button"
                >
                    {form.processing && <Spinner />}
                    {t('Save design')}
                </Button>
            )}
        </form>
    );
}
