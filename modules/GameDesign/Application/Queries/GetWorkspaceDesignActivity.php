<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Application\DTOs\WorkspaceDesignActivity;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What this module can tell the app's home screen about a workspace.
 *
 * The workspace-wide counterpart to {@see GetGameDashboard}: the dashboard
 * asks a handful of questions about every game at once, and asking them
 * together here keeps the controller from making separate trips and the screen
 * from deciding what a studio's overview consists of.
 *
 * Always workspace-scoped, and the workspace is a required argument rather
 * than a filter — there is no "across every studio" query to call by mistake.
 *
 * Resolution is unauthorized on purpose: gathering the figures and deciding
 * who may read them are separate steps, and the caller runs the policy against
 * the workspace first.
 */
final class GetWorkspaceDesignActivity
{
    /**
     * How many games the home screen lists before deferring to the games
     * screen. Enough to recognise what is being worked on, few enough that the
     * card stays a summary rather than becoming a second games list.
     */
    private const RECENT_GAMES = 5;

    public function __construct(private readonly GameRepository $games) {}

    public function handle(Workspace $workspace): WorkspaceDesignActivity
    {
        return new WorkspaceDesignActivity(
            gameCount: $this->games->countForWorkspace($workspace),
            versionCount: $this->games->countVersionsInWorkspace($workspace),
            gamesByStatus: $this->complete(
                $this->games->statusTallyForWorkspace($workspace),
                array_map(fn (GameStatus $status): string => $status->value, GameStatus::cases()),
            ),
            gamesByDesignPhase: $this->complete(
                $this->games->designPhaseTallyForWorkspace($workspace),
                array_map(fn (DesignPhase $phase): string => $phase->value, DesignPhase::cases()),
            ),
            recentGames: $this->games->recentlyUpdatedInWorkspace($workspace, self::RECENT_GAMES),
        );
    }

    /**
     * Fill in the values the database had no rows for.
     *
     * The repository can only report what it holds, so a workspace with no
     * archived games reports no `archived` key at all. A distribution with
     * holes in it cannot be drawn, and the enum is the only thing that knows
     * both the full set and the order to read it in.
     *
     * @param  array<string, int>  $tally
     * @param  list<string>  $values
     * @return array<string, int>
     */
    private function complete(array $tally, array $values): array
    {
        $complete = [];

        foreach ($values as $value) {
            $complete[$value] = $tally[$value] ?? 0;
        }

        return $complete;
    }
}
