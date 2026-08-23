import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/lib/i18n';
import type { VocabularyOption } from '../types/game-rules';

type OptionSelectProps = {
    value: string;
    options: readonly (VocabularyOption & { description?: string })[];
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;

    /**
     * The label for "none of these", when the field is genuinely optional.
     *
     * A rule with no phase and an action with no economy action are both ordinary states, so the empty
     * choice has to be selectable rather than only being the initial value.
     */
    emptyLabel?: string;

    id?: string;
};

/**
 * Every picker on the rules screens.
 *
 * The options come from the server, already worded, and this renders them. Nothing here holds a copy of a
 * vocabulary — which is what stops a rule type renamed in the domain from still reading the old way in the
 * interface.
 *
 * The empty value is `__none__` rather than `''` because Radix reserves the empty string for "nothing
 * selected", and a designer clearing a phase needs to be able to *choose* nothing. It is translated back to
 * `''` on the way out, which is what the server reads as "not set".
 */
export default function OptionSelect({
    value,
    options,
    onChange,
    placeholder,
    disabled,
    emptyLabel,
    id,
}: OptionSelectProps) {
    const { t } = useTranslation();

    return (
        <Select
            value={value === '' ? NONE : value}
            disabled={disabled}
            onValueChange={(next) => onChange(next === NONE ? '' : next)}
        >
            <SelectTrigger id={id}>
                <SelectValue placeholder={placeholder ?? t('Choose one')} />
            </SelectTrigger>

            <SelectContent>
                {emptyLabel !== undefined && (
                    <SelectItem value={NONE}>{emptyLabel}</SelectItem>
                )}

                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

const NONE = '__none__';
