import { router } from '@inertiajs/react';
import games from '@/routes/games';
import type { DesignRecordInput } from '../schemas/design-record';
import { toOptionalNumber } from '../schemas/design-record';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Record what has been decided about a game's design.
 *
 * The numbers are coerced here rather than in the form, so an empty box arrives
 * as null — "the designer has not decided" — instead of as `0` or `NaN`, both of
 * which the server would store as decisions.
 *
 * Every field is sent on every save, including the empty ones, because the
 * server treats an update as a replacement. That is what makes deleting a pitch
 * possible, and it is why the form has to hold the whole record rather than a
 * patch of it.
 */
export function updateDesignRecord(
    workspace: string,
    game: string,
    input: DesignRecordInput,
    options: MutationOptions = {},
): void {
    router.patch(
        games.design.update.url({ workspace, game }),
        {
            pitch: input.pitch.trim() || null,
            player_count_min: toOptionalNumber(input.player_count_min),
            player_count_max: toOptionalNumber(input.player_count_max),
            play_time_min: toOptionalNumber(input.play_time_min),
            play_time_max: toOptionalNumber(input.play_time_max),
            target_age_min: toOptionalNumber(input.target_age_min),
            complexity: input.complexity || null,
            audience: input.audience.trim() || null,
            core_action: input.core_action.trim() || null,
            core_cost: input.core_cost.trim() || null,
            core_reward: input.core_reward.trim() || null,
            win_condition: input.win_condition.trim() || null,
            failure_condition: input.failure_condition.trim() || null,
            mechanics: input.mechanics,
        },
        toVisitOptions(options),
    );
}
