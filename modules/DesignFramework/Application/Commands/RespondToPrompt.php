<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\PromptResponseData;
use Modules\DesignFramework\Application\Services\FrameworkContentLocator;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Events\PromptAnswered;
use Modules\DesignFramework\Domain\Models\DesignPrompt;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Write a game's answer to one of the framework's questions.
 *
 * The framework asks "what is your core player experience?"; this records the paragraph a
 * studio wrote about their game. Held against the adoption rather than against the prompt,
 * for the same reason as every other record in this family: the prompt is asked of
 * everybody following the version.
 *
 * Answering again overwrites, and `answered_at` moves while `created_at` does not — which
 * is how {@see PromptResponse::wasRevised()} can tell a first answer from a rewrite
 * without a history table. A prompt asks what the design is now, not what it used to be;
 * a studio wanting to see how their thinking changed is asking for something this
 * deliberately does not try to be.
 *
 * The response text is not put on the event. Answers to prompts are a studio's design
 * thinking, and an event carrying them would push that into every log, queue payload and
 * consumer that ever subscribes.
 */
final class RespondToPrompt
{
    public function __construct(
        private readonly GameFrameworkGuard $guard,
        private readonly FrameworkContentLocator $content,
        private readonly GameFrameworkRepository $adoptions,
    ) {}

    public function handle(
        User $author,
        GameFramework $adoption,
        DesignPrompt $prompt,
        PromptResponseData $data,
    ): PromptResponse {
        $this->guard->ensureAdoptionAcceptsProgress($adoption);
        $this->content->ensureAdopted($adoption, $prompt);

        $existing = $this->adoptions->findResponse($adoption, $prompt);

        $answeredAt = now()->toImmutable();
        $wasRevised = $existing !== null;

        $response = $existing ?? new PromptResponse;

        $response->fill(['response' => $data->response]);

        $response->game_framework_id = $adoption->getKey();
        $response->prompt_id = $prompt->getKey();
        $response->answered_by = $author->id;
        $response->answered_at = $answeredAt;

        $response->save();

        $response->setRelation('gameFramework', $adoption);
        $response->setRelation('prompt', $prompt);
        $response->setRelation('author', $author);

        event(new PromptAnswered(
            responseId: $response->id,
            gameFrameworkId: $adoption->getKey(),
            gameId: $adoption->game_id,
            promptId: $prompt->getKey(),
            wasRevised: $wasRevised,
            answeredBy: $author->id,
            answeredAt: $answeredAt->toDateTimeImmutable(),
        ));

        return $response;
    }
}
