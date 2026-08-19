import { Link } from '@inertiajs/react';
import { Camera, GitCompare } from 'lucide-react';
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
import { comparisonUrl, createBalanceSnapshot } from '../api';
import { useBalanceForm } from '../hooks/use-balance-form';
import type { ProfileScope } from '../hooks/use-balance-scope';
import {
    emptySnapshotInput,
    SNAPSHOT_NAME_MAX_LENGTH,
    SNAPSHOT_NAME_MIN_LENGTH,
    validateLength,
} from '../schemas/game-economy';
import type { BalanceSnapshot } from '../types/game-economy';

type BalanceSnapshotListProps = {
    snapshots: BalanceSnapshot[];
    scope: ProfileScope;
    canSnapshot: boolean;
};

/**
 * The frozen states of a configuration, and the pair to compare.
 *
 * Snapshots have no edit and no delete, and the list does not offer either. A snapshot is what the economy
 * *was*: rewriting one would change what every playtest run against it was played under, and deleting one
 * would remove the only record of it.
 *
 * Taking a snapshot is offered even on an archived profile, because "keep a copy of what we shipped" is a
 * reason to take one rather than a reason to refuse.
 */
export default function BalanceSnapshotList({
    snapshots,
    scope,
    canSnapshot,
}: BalanceSnapshotListProps) {
    const { t } = useTranslation();
    const [taking, setTaking] = useState(false);
    const [from, setFrom] = useState(snapshots[1]?.id ?? '');
    const [to, setTo] = useState(snapshots[0]?.id ?? '');

    const form = useBalanceForm({
        initial: emptySnapshotInput,
        validate: (input) => ({
            name:
                validateLength(input.name, {
                    min: SNAPSHOT_NAME_MIN_LENGTH,
                    max: SNAPSHOT_NAME_MAX_LENGTH,
                    tooShort: t('Give the snapshot a name, such as v1.2.'),
                    tooLong: t('That name is too long.'),
                }) ?? undefined,
        }),
        perform: (input, mutation) =>
            createBalanceSnapshot(scope, input, mutation),
        onSuccess: () => setTaking(false),
    });

    const comparable =
        snapshots.length > 1 && from !== '' && to !== '' && from !== to;

    return (
        <div className="space-y-4">
            {snapshots.length === 0 ? (
                <p
                    className="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                    data-test="snapshots-empty"
                >
                    {t(
                        'No snapshots yet. Take one before a playtest and the numbers it was played against stay readable afterwards.',
                    )}
                </p>
            ) : (
                <ul className="divide-y rounded-md border">
                    {snapshots.map((snapshot) => (
                        <li
                            key={snapshot.id}
                            className="flex flex-wrap items-center justify-between gap-3 p-3"
                            data-test={`snapshot-${snapshot.id}`}
                        >
                            <div className="min-w-0">
                                <span className="block font-medium" dir="auto">
                                    {snapshot.name}
                                </span>

                                <span className="block text-xs text-muted-foreground">
                                    {t(
                                        ':resources resources · :actions actions · :variables variables',
                                        {
                                            resources: String(
                                                snapshot.tally.resources ?? 0,
                                            ),
                                            actions: String(
                                                snapshot.tally.actions ?? 0,
                                            ),
                                            variables: String(
                                                snapshot.tally.variables ?? 0,
                                            ),
                                        },
                                    )}
                                </span>
                            </div>

                            {snapshot.created_at && (
                                <time
                                    className="text-xs text-muted-foreground"
                                    dateTime={snapshot.created_at}
                                    dir="ltr"
                                >
                                    {snapshot.created_at.slice(0, 10)}
                                </time>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {snapshots.length > 1 && (
                <div className="flex flex-wrap items-end gap-2">
                    <div className="grid gap-2">
                        <Label htmlFor="compare-from">{t('From')}</Label>

                        <Select value={from} onValueChange={setFrom}>
                            <SelectTrigger
                                id="compare-from"
                                className="w-44"
                                data-test="compare-from-picker"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {snapshots.map((snapshot) => (
                                    <SelectItem
                                        key={snapshot.id}
                                        value={snapshot.id}
                                    >
                                        {snapshot.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="compare-to">{t('To')}</Label>

                        <Select value={to} onValueChange={setTo}>
                            <SelectTrigger
                                id="compare-to"
                                className="w-44"
                                data-test="compare-to-picker"
                            >
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                {snapshots.map((snapshot) => (
                                    <SelectItem
                                        key={snapshot.id}
                                        value={snapshot.id}
                                    >
                                        {snapshot.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Button
                        size="sm"
                        variant="outline"
                        asChild={comparable}
                        disabled={!comparable}
                        data-test="compare-button"
                    >
                        {comparable ? (
                            <Link href={comparisonUrl(scope, from, to)}>
                                <GitCompare className="size-4" />
                                {t('Compare')}
                            </Link>
                        ) : (
                            <span>
                                <GitCompare className="size-4" />
                                {t('Compare')}
                            </span>
                        )}
                    </Button>
                </div>
            )}

            {canSnapshot && (
                <>
                    {taking ? (
                        <form
                            className="flex flex-wrap items-end gap-2 rounded-md border p-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.submit();
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="snapshot-name">
                                    {t('Name')}
                                </Label>

                                <Input
                                    id="snapshot-name"
                                    value={form.input.name}
                                    onChange={(event) =>
                                        form.setField(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="v1.2"
                                    autoComplete="off"
                                    className="w-40"
                                    data-test="snapshot-name-input"
                                />

                                <InputError message={form.errors.name} />
                            </div>

                            <Button
                                type="submit"
                                size="sm"
                                disabled={form.processing}
                                data-test="submit-snapshot-button"
                            >
                                {form.processing && <Spinner />}
                                {t('Take snapshot')}
                            </Button>

                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                onClick={() => {
                                    setTaking(false);
                                    form.reset();
                                }}
                            >
                                {t('Cancel')}
                            </Button>
                        </form>
                    ) : (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setTaking(true)}
                            data-test="take-snapshot-button"
                        >
                            <Camera className="size-4" />
                            {t('Take snapshot')}
                        </Button>
                    )}
                </>
            )}
        </div>
    );
}
