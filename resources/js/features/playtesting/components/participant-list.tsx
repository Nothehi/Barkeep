import { UserPlus, X } from 'lucide-react';
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
import type { User } from '@/features/auth';
import { useFormatters, useTranslation } from '@/lib/i18n';
import { useParticipants } from '../hooks/use-participants';
import type {
    Participant,
    PlaytestOptions,
    PlaytestSession,
} from '../types/playtest';

type ParticipantListProps = {
    session: PlaytestSession;
    participants: Participant[];
    teammates: User[];
    options: PlaytestOptions;
    workspace: string;
    game: string;
    playtest: string;
};

/**
 * Who is at the table, and the one-line form that adds somebody.
 *
 * A name and a role, on one row, submitted by pressing enter. Participants are
 * added in a burst as everybody sits down, so the form has to survive being
 * used four times in twenty seconds — which is why it resets fully after each
 * success and keeps focus where it is.
 *
 * The teammate picker is a shortcut, not a requirement. Most people at a
 * playtest have no Barkeep account and are recorded by name alone; choosing a
 * teammate simply fills the name in and links the account, which the server
 * only allows for people who already share the workspace.
 */
const GUEST = 'guest';

export default function ParticipantList({
    session,
    participants,
    teammates,
    options,
    workspace,
    game,
    playtest,
}: ParticipantListProps) {
    const { t } = useTranslation();
    const { formatNumber } = useFormatters();
    const form = useParticipants(
        workspace,
        game,
        playtest,
        session.id,
        participants,
    );

    const canManage = session.permissions.canManageParticipants;

    const chooseTeammate = (value: string) => {
        if (value === GUEST) {
            form.setField('user_id', '');

            return;
        }

        const teammate = teammates.find((candidate) => candidate.id === value);

        form.setField('user_id', value);

        if (teammate) {
            form.setField('display_name', teammate.name);
        }
    };

    return (
        <section className="space-y-3" data-test="participant-list">
            <div className="flex items-center justify-between gap-3">
                <h2 className="font-semibold">
                    {t('Participants')}{' '}
                    <span className="text-sm font-normal text-muted-foreground">
                        {t(':total at the table · :players playing', {
                            total: formatNumber(participants.length),
                            players: formatNumber(form.players.length),
                        })}
                    </span>
                </h2>
            </div>

            {canManage && (
                <form
                    className="grid gap-3 rounded-lg border p-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.submit();
                    }}
                >
                    <div className="grid gap-1.5">
                        <Label htmlFor="participant-name" className="text-xs">
                            {t('Name')}
                        </Label>

                        <Input
                            id="participant-name"
                            value={form.input.display_name}
                            onChange={(event) =>
                                form.setField(
                                    'display_name',
                                    event.target.value,
                                )
                            }
                            placeholder={t('Sam')}
                            autoComplete="off"
                            data-test="participant-name-input"
                        />
                    </div>

                    <div className="grid gap-1.5">
                        <Label htmlFor="participant-role" className="text-xs">
                            {t('Role')}
                        </Label>

                        <Select
                            value={form.input.role}
                            onValueChange={(value) =>
                                form.setField(
                                    'role',
                                    value as Participant['role'],
                                )
                            }
                        >
                            <SelectTrigger
                                id="participant-role"
                                className="w-36"
                                data-test="participant-role-picker"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {options.roles.map((role) => (
                                    <SelectItem
                                        key={role.value}
                                        value={role.value}
                                    >
                                        {role.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {teammates.length > 0 && (
                        <div className="grid gap-1.5">
                            <Label
                                htmlFor="participant-account"
                                className="text-xs"
                            >
                                {t('Account')}
                            </Label>

                            <Select
                                value={form.input.user_id || GUEST}
                                onValueChange={chooseTeammate}
                            >
                                <SelectTrigger
                                    id="participant-account"
                                    className="w-40"
                                    data-test="participant-account-picker"
                                >
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectItem value={GUEST}>
                                        {t('Guest')}
                                    </SelectItem>

                                    {teammates.map((teammate) => (
                                        <SelectItem
                                            key={teammate.id}
                                            value={teammate.id}
                                            dir="auto"
                                        >
                                            {teammate.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <Button
                        type="submit"
                        disabled={form.processing}
                        data-test="add-participant-button"
                    >
                        {form.processing ? (
                            <Spinner />
                        ) : (
                            <UserPlus className="size-4" />
                        )}
                        {t('Add')}
                    </Button>

                    <div className="sm:col-span-full">
                        <InputError message={form.errors.display_name} />
                        <InputError message={form.errors.user_id} />
                    </div>
                </form>
            )}

            {participants.length === 0 ? (
                <p
                    className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground"
                    data-test="participants-empty"
                >
                    {t('Nobody added yet.')}
                </p>
            ) : (
                <ul className="divide-y rounded-lg border">
                    {participants.map((participant) => (
                        <li
                            key={participant.id}
                            className="flex items-center justify-between gap-3 px-3 py-2"
                        >
                            <div className="flex min-w-0 items-center gap-2">
                                <span
                                    className="truncate font-medium"
                                    dir="auto"
                                >
                                    {participant.display_name}
                                </span>

                                <Badge variant="outline">
                                    {participant.role_label}
                                </Badge>

                                {participant.is_registered && (
                                    <Badge variant="secondary">
                                        {t('Member')}
                                    </Badge>
                                )}
                            </div>

                            {canManage && (
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    aria-label={t('Remove :name', {
                                        name: participant.display_name,
                                    })}
                                    onClick={() => form.remove(participant.id)}
                                    data-test={`remove-participant-${participant.id}`}
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
