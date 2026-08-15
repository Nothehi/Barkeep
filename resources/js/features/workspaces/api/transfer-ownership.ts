import { router } from '@inertiajs/react';
import ownership from '@/routes/workspaces/ownership';
import type { TransferOwnershipInput } from '../schemas/workspace';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Hand a workspace to another member.
 *
 * The outgoing owner keeps working in the workspace under the role given in
 * `role`, which defaults to administrator on the server.
 */
export function transferOwnership(
    slug: string,
    input: TransferOwnershipInput,
    options: MutationOptions = {},
): void {
    router.post(
        ownership.transfer.url(slug),
        { ...input },
        toVisitOptions(options),
    );
}
