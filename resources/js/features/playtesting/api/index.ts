/**
 * The playtesting feature's server calls.
 *
 * Split by direction rather than by resource:
 *
 * - reads go over the JSON API and return data (`get*`);
 * - writes are Inertia visits and return nothing, because the server answers
 *   them with a redirect and, where it is worth saying, a flash message.
 *
 * The writes matter more than usual here. A live session screen is edited
 * repeatedly while something else is happening in the room, and every write
 * coming back as a reloaded page is what keeps what is on screen equal to what
 * the server actually stored.
 */

export { addParticipant } from './add-participant';
export { cancelPlaytest } from './cancel-playtest';
export { cancelSession } from './cancel-session';
export { PlaytestApiError } from './client';
export { completePlaytest } from './complete-playtest';
export { completeSession } from './complete-session';
export { createFeedback } from './create-feedback';
export { createObservation } from './create-observation';
export { createPlaytest } from './create-playtest';
export { createSession } from './create-session';
export { deleteFeedback } from './delete-feedback';
export { deleteObservation } from './delete-observation';
export { getPlaytest } from './get-playtest';
export { getPlaytests } from './get-playtests';
export { getPlaytestSummary } from './get-playtest-summary';
export { getSessions } from './get-sessions';
export type { MutationOptions } from './mutation';
export { removeParticipant } from './remove-participant';
export { startSession } from './start-session';
export { updateFeedback } from './update-feedback';
export { updateObservation } from './update-observation';
export { updatePlaytest } from './update-playtest';
export { updateSession } from './update-session';
