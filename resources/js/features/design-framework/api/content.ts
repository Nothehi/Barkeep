import { router } from '@inertiajs/react';
import checklists from '@/routes/frameworks/versions/checklists';
import items from '@/routes/frameworks/versions/checklists/items';
import criteria from '@/routes/frameworks/versions/criteria';
import phases from '@/routes/frameworks/versions/phases';
import practices from '@/routes/frameworks/versions/practices';
import principles from '@/routes/frameworks/versions/principles';
import prompts from '@/routes/frameworks/versions/prompts';
import type { FrameworkContentStatus } from '../types/framework';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * The kinds of content an edition is built from.
 *
 * Checklist items are absent, and deliberately: an item belongs to a
 * checklist rather than to the edition, so its address is one segment longer
 * and its calls are separate ones below.
 */
export type ContentType =
    | 'phases'
    | 'principles'
    | 'criteria'
    | 'practices'
    | 'prompts'
    | 'checklists';

/**
 * The routes for each kind, which are the same three routes throughout.
 *
 * Written as a map rather than as eighteen exported functions because the
 * uniformity is real: the server exposes create, update and reorder for every
 * content type on the same shape of URL, and duplicating that here would be
 * six chances for one of them to drift.
 */
const ROUTES = {
    phases,
    principles,
    criteria,
    practices,
    prompts,
    checklists,
} as const;

/**
 * What a piece of content is written from.
 *
 * A union of every kind's fields rather than six types, because the builder
 * renders one form per kind from one component. The server validates the
 * fields its own kind accepts and ignores the rest, so sending `prompt` to a
 * principle is refused there rather than being made unrepresentable here.
 *
 * `phase_id` is nullable throughout: content filed under no phase applies
 * across the whole methodology. `position` is absent because it is never an
 * input — new content is appended, and moving it is `reorderContent`.
 */
export type ContentInput = {
    name?: string;
    title?: string;
    description?: string | null;
    instructions?: string | null;
    prompt?: string | null;
    phase_id?: string | null;
    status?: FrameworkContentStatus;
};

/**
 * Add a piece of content to a draft edition.
 */
export function createContent(
    framework: string,
    version: number,
    type: ContentType,
    input: ContentInput,
    options: MutationOptions = {},
): void {
    router.post(
        ROUTES[type].store.url({ framework, version }),
        input,
        toVisitOptions(options),
    );
}

/**
 * Change a piece of content in a draft edition.
 */
export function updateContent(
    framework: string,
    version: number,
    type: ContentType,
    id: string,
    input: ContentInput,
    options: MutationOptions = {},
): void {
    router.patch(
        ROUTES[type].update.url([framework, version, id]),
        input,
        toVisitOptions(options),
    );
}

/**
 * Move a piece of content to another place among its siblings.
 *
 * Positions are one-based and contiguous, and the server rewrites the whole
 * list rather than nudging neighbours — which is what makes every reorder
 * self-healing. A position past the end is refused rather than clamped: a
 * clamp turns a drag that landed in the wrong place into a move nobody asked
 * for, silently.
 */
export function reorderContent(
    framework: string,
    version: number,
    type: ContentType,
    id: string,
    position: number,
    options: MutationOptions = {},
): void {
    router.post(
        ROUTES[type].reorder.url([framework, version, id]),
        { position },
        toVisitOptions(options),
    );
}

/**
 * What one requirement on a checklist is written from.
 */
export type ChecklistItemInput = {
    title: string;
    description?: string | null;
    required?: boolean;
};

/**
 * Add a requirement to a checklist in a draft edition.
 */
export function createChecklistItem(
    framework: string,
    version: number,
    checklist: string,
    input: ChecklistItemInput,
    options: MutationOptions = {},
): void {
    router.post(
        items.store.url([framework, version, checklist]),
        input,
        toVisitOptions(options),
    );
}

/**
 * Change a requirement on a checklist in a draft edition.
 */
export function updateChecklistItem(
    framework: string,
    version: number,
    checklist: string,
    item: string,
    input: Partial<ChecklistItemInput>,
    options: MutationOptions = {},
): void {
    router.patch(
        items.update.url([framework, version, checklist, item]),
        input,
        toVisitOptions(options),
    );
}

/**
 * Move a requirement to another place on its checklist.
 *
 * Ordered within its own list rather than within the edition, which is why an
 * item's position is a smaller number than its neighbours' ids would suggest.
 */
export function reorderChecklistItem(
    framework: string,
    version: number,
    checklist: string,
    item: string,
    position: number,
    options: MutationOptions = {},
): void {
    router.post(
        items.reorder.url([framework, version, checklist, item]),
        { position },
        toVisitOptions(options),
    );
}
