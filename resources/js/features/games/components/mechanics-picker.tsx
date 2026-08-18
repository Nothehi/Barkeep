import { Label } from '@/components/ui/label';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTranslation } from '@/lib/i18n';
import type { Mechanic } from '../types/mechanic';

type MechanicsPickerProps = {
    mechanics: Mechanic[];
    claimed: string[];
    onChange: (ids: string[]) => void;
    disabled?: boolean;
};

/**
 * Which terms from the shared vocabulary this game claims.
 *
 * Chips rather than a dropdown, because the answer is several things and a
 * designer needs to see all of them at once — a multi-select that hides its own
 * selection behind a trigger makes "what have I said this game is?" a question
 * you have to open something to answer.
 *
 * Grouped by category, walking the list the server already ordered rather than
 * sorting again, so that order has one definition. The headings are the
 * vocabulary's own, so this component never learns what a category is called.
 *
 * `ToggleGroup type="multiple"` rather than a checkbox grid: both exist in the
 * kit and neither needs a new dependency, but a claim about a design is a
 * statement rather than a task, and chips read that way where a column of ticks
 * reads as a to-do list.
 *
 * The default variant is used rather than `outline`, whose styling joins items
 * into one segmented control by stripping their inner borders — correct for a
 * three-way switch, wrong for a wrapping set of independent chips.
 */
export default function MechanicsPicker({
    mechanics,
    claimed,
    onChange,
    disabled = false,
}: MechanicsPickerProps) {
    const { t } = useTranslation();

    if (mechanics.length === 0) {
        return (
            <p
                className="rounded-md border border-dashed px-3 py-4 text-sm text-muted-foreground"
                data-test="mechanics-picker-empty"
            >
                {t(
                    'The vocabulary is empty, so there is nothing to claim yet.',
                )}
            </p>
        );
    }

    const groups: { label: string; mechanics: Mechanic[] }[] = [];

    for (const mechanic of mechanics) {
        const last = groups.at(-1);

        if (last?.label === mechanic.category_label) {
            last.mechanics.push(mechanic);
            continue;
        }

        groups.push({ label: mechanic.category_label, mechanics: [mechanic] });
    }

    /*
     * One group wrapping every category, not one per heading. A Radix toggle
     * group's value is the set of its own pressed items, so a group per category
     * would report only that category's selection on change — and claiming a
     * mechanic under "Economy" would silently drop everything claimed under
     * "Turn structure". The headings live inside the single root instead.
     */
    return (
        <ToggleGroup
            type="multiple"
            value={claimed}
            onValueChange={onChange}
            size="sm"
            className="flex flex-col items-stretch gap-4 rounded-none"
            disabled={disabled}
            data-test="mechanics-picker"
        >
            {groups.map((group) => (
                <div key={group.label} className="space-y-2">
                    <Label className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        {group.label}
                    </Label>

                    <div className="flex flex-wrap items-start gap-2">
                        {group.mechanics.map((mechanic) => (
                            <ToggleGroupItem
                                key={mechanic.id}
                                value={mechanic.id}
                                title={mechanic.description ?? undefined}
                                className="rounded-md border"
                                data-test={`mechanic-toggle-${mechanic.slug}`}
                                dir="auto"
                            >
                                {mechanic.name}
                            </ToggleGroupItem>
                        ))}
                    </div>
                </div>
            ))}
        </ToggleGroup>
    );
}
