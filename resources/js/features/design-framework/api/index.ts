/**
 * The design framework module's write surface.
 *
 * Every call here is an Inertia visit. There is no JSON read client: framework
 * screens arrive with everything they show, and the server answers each write
 * with a redirect, so the page a designer sees after ticking something is the
 * page the server rendered from what it stored.
 */

export { adoptFramework } from './adopt-framework';
export { completeChecklistItem } from './complete-checklist-item';
export { completePractice } from './complete-practice';
export {
    createChecklistItem,
    createContent,
    reorderChecklistItem,
    reorderContent,
    updateChecklistItem,
    updateContent,
} from './content';
export type { ChecklistItemInput, ContentInput, ContentType } from './content';
export { evaluateCriterion } from './evaluate-criterion';
export {
    createFramework,
    createVersion,
    moveFramework,
    moveVersion,
    updateFramework,
    updateVersion,
} from './frameworks';
export type { FrameworkInput, VersionInput } from './frameworks';
export { moveAdoption } from './move-adoption';
export type { MutationOptions } from './mutation';
export { respondToPrompt } from './respond-to-prompt';
