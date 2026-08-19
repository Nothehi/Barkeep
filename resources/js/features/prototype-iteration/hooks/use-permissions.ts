import type {
    Iteration,
    IterationPermissions,
    Prototype,
    PrototypePermissions,
} from '../types/prototype-iteration';

/**
 * What the signed in account may do with a prototype, as the server worked it out.
 *
 * A hook rather than a plain property read so that every screen goes through one place, and so that a record
 * arriving without its permission map — which happens when a card resource is rendered where a full one was
 * expected — degrades to "may do nothing" rather than crashing on an undefined.
 *
 * These decide what the interface offers, never what the server allows. Every one is checked again on the
 * request that performs the action.
 */
export function usePrototypePermissions(
    prototype: Prototype,
): PrototypePermissions {
    return (
        prototype.permissions ?? {
            canView: false,
            canUpdate: false,
            canArchive: false,
            canCreateVersion: false,
        }
    );
}

/**
 * What the signed in account may do with a design cycle.
 *
 * `canRecordWork` is the one nearly every control on the iteration screen reads: "may design work be added to
 * this cycle?" is a single question with a single answer, and asking it once is what stops eight controls
 * from drifting apart as the rules change.
 */
export function useIterationPermissions(
    iteration: Iteration,
): IterationPermissions {
    return (
        iteration.permissions ?? {
            canView: false,
            canUpdate: false,
            canStart: false,
            canComplete: false,
            canCancel: false,
            canRecordWork: false,
            canAttachPlaytest: false,
            canCreateGameVersion: false,
        }
    );
}
