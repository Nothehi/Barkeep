type GameProgressProps = {
    position: number;
    total: number;
    label: string;
};

/**
 * How far along the design arc a game currently sits.
 *
 * Progress, not achievement. The bar says where the game is in the sequence
 * of phases; it does not claim the game is that fraction finished, and the
 * caption says which phase rather than a percentage so nobody reads it that
 * way.
 *
 * Both numbers come from the server, so the arc can grow a phase without this
 * component learning about it.
 */
export default function GameProgress({
    position,
    total,
    label,
}: GameProgressProps) {
    const percent = total > 0 ? Math.round((position / total) * 100) : 0;

    return (
        <div className="space-y-1.5">
            <div className="flex items-baseline justify-between gap-2">
                <span className="text-sm font-medium">{label}</span>
                <span className="text-xs text-muted-foreground">
                    Phase {position} of {total}
                </span>
            </div>

            <div
                className="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-valuenow={position}
                aria-valuemin={1}
                aria-valuemax={total}
                aria-label={`Design phase: ${label}`}
            >
                <div
                    className="h-full rounded-full bg-primary transition-all"
                    style={{ width: `${percent}%` }}
                />
            </div>
        </div>
    );
}
