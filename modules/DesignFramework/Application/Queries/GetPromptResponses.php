<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;

/**
 * What a game's designers have written in answer to the framework's questions.
 *
 * The most valuable read in the module. These paragraphs are a studio's design thinking,
 * which is why they are scoped to the adoption and never listed across games, and why the
 * event that announces one carries no text.
 */
final class GetPromptResponses
{
    public function __construct(private readonly GameFrameworkRepository $adoptions) {}

    /**
     * @return Collection<int, PromptResponse>
     */
    public function handle(GameFramework $adoption): Collection
    {
        return $this->adoptions->responsesOf($adoption);
    }
}
