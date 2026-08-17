/**
 * The design vocabulary shapes the server sends.
 *
 * Mirrors `Modules\GameDesign\Presentation\Http\Resources\MechanicResource`.
 * That is the authoritative shape — when it changes, change this too.
 *
 * A mechanic belongs to the platform rather than to a workspace, which is why
 * nothing here carries a workspace or a game. It is also why the permissions
 * are about curating a shared list rather than about standing inside a studio.
 */

/**
 * The part of a design a mechanic is about.
 *
 * Used to group the picker, so a designer can find "how do turns work" without
 * reading a hundred terms to discover the list contains nothing they need.
 */
export type MechanicCategory =
    | 'turn_structure'
    | 'economy'
    | 'space'
    | 'cards'
    | 'interaction'
    | 'uncertainty'
    | 'scoring';

/**
 * Whether a term is still offered.
 *
 * There is no draft: a mechanic is a word with a definition, and there is
 * nothing to work up to in private. Retired terms stay readable so the games
 * that claimed them keep saying what they said.
 */
export type MechanicStatus = 'published' | 'archived';

/**
 * What the signed in account may do with one term.
 *
 * Computed from the policy server side. It decides what the interface offers,
 * never what the server allows.
 */
export type MechanicPermissions = {
    canView: boolean;
    canUpdate: boolean;
    canArchive: boolean;
};

/**
 * One term in the vocabulary.
 *
 * `is_available` is sent rather than derived from the status, because "may a
 * game claim this?" is the only question a picker asks and working it out on
 * the client would be the client reimplementing the rule.
 */
export type Mechanic = {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    category: MechanicCategory;
    category_label: string;
    category_position: number;
    status: MechanicStatus;
    status_label: string;
    is_available: boolean;
    created_at: string | null;
    updated_at: string | null;
    permissions: MechanicPermissions;
};

/**
 * The choices the vocabulary screen offers, worded by the server so the
 * labels, the ordering and the set have one definition.
 */
export type MechanicOptions = {
    categories: {
        value: MechanicCategory;
        label: string;
        description: string;
        position: number;
    }[];
};
