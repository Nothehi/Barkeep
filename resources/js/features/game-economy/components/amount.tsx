import { cn } from '@/lib/utils';

/**
 * One exact amount, rendered.
 *
 * Every number in this module arrives as a string and stays one. This component exists so that no screen is
 * ever tempted to `Number(...)` an amount in order to format it — parsing is precisely where a designer's
 * 0.1 stops being 0.1, and the server has already produced the string it wants shown.
 *
 * `dir="ltr"` on the value is not decoration. Persian is a supported locale, so these are read inside an RTL
 * page, and a bare number with a minus sign or a unit beside it will reorder unless it is isolated.
 */
export default function Amount({
    value,
    unit,
    signed = false,
    tone,
    className,
}: {
    value: string | null;
    unit?: string | null;
    /**
     * Show a leading `+` on positive numbers.
     *
     * For a net flow, "+4" and "4" say different things — the first is a surplus and the second is just a
     * quantity — so the sign is opt-in rather than always on.
     */
    signed?: boolean;
    tone?: 'positive' | 'negative' | 'neutral';
    className?: string;
}) {
    if (value === null) {
        return (
            <span className={cn('text-muted-foreground', className)}>—</span>
        );
    }

    const prefix =
        signed && !value.startsWith('-') && !isZeroAmount(value) ? '+' : '';

    const tones = {
        positive: 'text-emerald-600 dark:text-emerald-400',
        negative: 'text-amber-600 dark:text-amber-400',
        neutral: 'text-muted-foreground',
    };

    return (
        <span
            className={cn(
                'tabular-nums',
                tone ? tones[tone] : undefined,
                className,
            )}
        >
            <span dir="ltr">
                {prefix}
                {value}
            </span>
            {unit ? (
                <span className="ms-1 text-xs text-muted-foreground" dir="auto">
                    {unit}
                </span>
            ) : null}
        </span>
    );
}

/**
 * Determine whether an amount is zero, without parsing it.
 *
 * The check is on the characters rather than on a parsed value, for the same reason nothing else in this
 * feature parses an amount: `Number('0.0000001')` is fine, but the habit is not, and a codebase where
 * amounts are sometimes numbers is one where a total eventually gets computed on the client.
 */
export function isZeroAmount(value: string): boolean {
    return /^-?0(\.0+)?$/.test(value.trim());
}

/**
 * The tone a net figure should be drawn in.
 *
 * Surplus reads as positive and deficit as a warning, which is the right way round for an economy: a
 * resource that piles up is usually fine, and one that runs out stops the game.
 */
export function toneForNet(value: string): 'positive' | 'negative' | 'neutral' {
    if (value.startsWith('-')) {
        return 'negative';
    }

    return isZeroAmount(value) ? 'neutral' : 'positive';
}
