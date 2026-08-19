import type { ProfileScope } from '../hooks/use-balance-scope';
import type { ActionLine, ResourceType } from '../types/game-economy';
import ActionLineEditor from './action-line-editor';

/**
 * What an action pays out.
 *
 * @see ActionCostEditor for why this is a named wrapper rather than a copy.
 */
export default function ActionRewardEditor(props: {
    rewards: ActionLine[];
    resources: ResourceType[];
    scope: ProfileScope;
    economyAction: string;
    canConfigure: boolean;
}) {
    const { rewards, ...rest } = props;

    return <ActionLineEditor kind="reward" lines={rewards} {...rest} />;
}
