import { router } from '@inertiajs/react';
import frameworks from '@/routes/frameworks';
import versions from '@/routes/frameworks/versions';
import type { FrameworkStatus } from '../types/framework';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * What a methodology, or one of its editions, is written from.
 *
 * No slug and no version number. Both are allocated by the server — the slug
 * from the name, the number in sequence — so nobody can claim v999 or reuse
 * an address that already means something to the studios reading it.
 */
export type FrameworkInput = {
    name: string;
    description?: string | null;
};

export type VersionInput = {
    name?: string | null;
    description?: string | null;
};

/**
 * Start writing a new methodology.
 *
 * The server answers with a redirect to it, because the only reason to create
 * a framework is to go and write it.
 */
export function createFramework(
    input: FrameworkInput,
    options: MutationOptions = {},
): void {
    router.post(frameworks.store.url(), input, toVisitOptions(options));
}

/**
 * Change a methodology's name or description.
 */
export function updateFramework(
    framework: string,
    input: FrameworkInput,
    options: MutationOptions = {},
): void {
    router.patch(
        frameworks.update.url({ framework }),
        input,
        toVisitOptions(options),
    );
}

/**
 * The endpoint each framework lifecycle move is posted to.
 *
 * Draft has no entry because nothing moves back to it: publishing a
 * methodology is what makes it adoptable, and un-publishing one that studios
 * are already working through would pull the ground out from under them.
 */
const FRAMEWORK_MOVES = {
    published: frameworks.publish,
    archived: frameworks.archive,
} as const;

/**
 * Move a methodology to another point in its lifecycle.
 */
export function moveFramework(
    framework: string,
    status: Exclude<FrameworkStatus, 'draft'>,
    options: MutationOptions = {},
): void {
    router.post(
        FRAMEWORK_MOVES[status].url({ framework }),
        {},
        toVisitOptions(options),
    );
}

/**
 * Cut the next edition of a methodology.
 */
export function createVersion(
    framework: string,
    input: VersionInput,
    options: MutationOptions = {},
): void {
    router.post(
        versions.store.url({ framework }),
        input,
        toVisitOptions(options),
    );
}

/**
 * Change a draft edition's name or description.
 *
 * Only a draft. Publishing an edition freezes it, which is what lets a game
 * on v1 keep reading the same questions for as long as it exists.
 */
export function updateVersion(
    framework: string,
    version: number,
    input: VersionInput,
    options: MutationOptions = {},
): void {
    router.patch(
        versions.update.url({ framework, version }),
        input,
        toVisitOptions(options),
    );
}

const VERSION_MOVES = {
    published: versions.publish,
    archived: versions.archive,
} as const;

/**
 * Move one edition to another point in its lifecycle.
 *
 * Publishing is the irreversible one, and the interface should say so before
 * it is pressed: from that moment the edition is what adopting studios read,
 * and it stops accepting changes.
 */
export function moveVersion(
    framework: string,
    version: number,
    status: Exclude<FrameworkStatus, 'draft'>,
    options: MutationOptions = {},
): void {
    router.post(
        VERSION_MOVES[status].url({ framework, version }),
        {},
        toVisitOptions(options),
    );
}
