import type { ProfileScope } from '../hooks/use-balance-scope';
import type { ActionLine, ResourceType } from '../types/game-economy';
import ActionLineEditor from './action-line-editor';

/**
 * What an action takes to perform.
 *
 * A named wrapper over {@link ActionLineEditor} rather than a copy of it. Costs and rewards take the same
 * input, so the editor is shared — but they are different things to a designer, they are written by
 * different commands, and the screen names them separately. Keeping the two names means a caller says which
 * it means rather than passing a string that could be the wrong one.
 */
export default function ActionCostEditor(props: {
    costs: ActionLine[];
    resources: ResourceType[];
    scope: ProfileScope;
    economyAction: string;
    canConfigure: boolean;
}) {
    const { costs, ...rest } = props;

    return <ActionLineEditor kind="cost" lines={costs} {...rest} />;
}
