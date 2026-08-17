/**
 * The game feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers
 *   them with a redirect and a flash message.
 */

export { archiveGame } from './archive-game';
export { changeDesignPhase } from './change-design-phase';
export { changeGameStatus } from './change-game-status';
export { GameApiError } from './client';
export { createGame } from './create-game';
export { createGameVersion } from './create-game-version';
export { getGame } from './get-game';
export { getGames } from './get-games';
export { getGameVersions } from './get-game-versions';
export { archiveMechanic, createMechanic, updateMechanic } from './mechanics';
export type { MechanicInput } from './mechanics';
export type { MutationOptions } from './mutation';
export { updateDesignRecord } from './update-design-record';
export { updateGame } from './update-game';
