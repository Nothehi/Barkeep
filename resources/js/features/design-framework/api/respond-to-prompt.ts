import { router } from '@inertiajs/react';
import prompts from '@/routes/games/framework/prompts';
import type { MutationOptions } from './mutation';
import { toVisitOptions } from './mutation';

/**
 * Write this game's answer to one of the methodology's questions.
 *
 * Answering again overwrites. A prompt asks what the design is now, not what
 * it used to be, and a studio wanting to see how its thinking changed is
 * asking for something the module deliberately does not try to be.
 */
export function respondToPrompt(
    workspace: string,
    game: string,
    prompt: string,
    response: string,
    options: MutationOptions = {},
): void {
    router.post(
        prompts.respond.url({ workspace, game, prompt }),
        { response },
        toVisitOptions(options),
    );
}
