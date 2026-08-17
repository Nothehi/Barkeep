/**
 * What has been decided about a game's design, as the server sends it.
 *
 * Mirrors `Modules\GameDesign\Presentation\Http\Resources\DesignRecordResource`.
 * That is the authoritative shape — when it changes, change this too.
 *
 * Every field is nullable because deciding is the work. A game in ideation has
 * answered none of this, and the difference between "not decided" and "decided
 * to leave blank" is exactly what a methodology's factual criteria read — so a
 * null here is information rather than a gap to paper over.
 */

import type { Mechanic } from './mechanic';

/**
 * How much a game asks of the table.
 *
 * "Weight", in the language designers use. There is deliberately no default:
 * a weight nobody has chosen is undecided rather than light.
 */
export type Complexity = 'party' | 'family' | 'gateway' | 'hobby' | 'heavy';

/**
 * A game's design record.
 *
 * The raw numbers and a rendered label travel together, because they have
 * different jobs: a form needs `player_count_min` for a box, and a heading needs
 * "2 to 4 players". Deriving the second here would put a formatting decision in
 * two places, one of which would eventually say "90 min" where the other said
 * "1 h 30 min".
 */
export type DesignRecord = {
    id: string;
    game_id: string;

    pitch: string | null;

    player_count_min: number | null;
    player_count_max: number | null;
    player_count_label: string | null;

    play_time_min: number | null;
    play_time_max: number | null;
    play_time_label: string | null;

    target_age_min: number | null;

    complexity: Complexity | null;
    complexity_label: string | null;
    complexity_description: string | null;

    audience: string | null;

    core_action: string | null;
    core_cost: string | null;
    core_reward: string | null;
    win_condition: string | null;
    failure_condition: string | null;
    has_complete_core_loop: boolean;

    mechanics?: Mechanic[];

    /**
     * Whether anything at all has been decided. Sent so a screen can tell an
     * untouched design from one deliberately left blank without comparing
     * thirteen fields against null.
     */
    is_empty: boolean;

    created_at: string | null;
    updated_at: string | null;
};

/**
 * The weights a designer may choose, worded by the server.
 */
export type ComplexityOptions = {
    complexities: {
        value: Complexity;
        label: string;
        description: string;
        position: number;
    }[];
};
