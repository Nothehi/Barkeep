<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\Playtesting\Application\DTOs\WorkspacePlaytestActivity;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What this module can tell the app's home screen about a workspace.
 *
 * Every other query here is scoped to one game, because that is how playtests
 * are read: from the project they belong to. This one is scoped to the
 * workspace instead, for the one screen that is about a studio rather than
 * about a design — and the scope is still a required argument, so there is no
 * "every playtest in the platform" query to reach for by mistake.
 *
 * Resolution is unauthorized on purpose: gathering the figures and deciding
 * who may read them are separate steps, and the caller runs the policy against
 * the workspace first.
 */
final class GetWorkspacePlaytestActivity
{
    /**
     * How many investigations the home screen lists before deferring to a
     * game's own playtests screen.
     */
    private const RECENT_PLAYTESTS = 5;

    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Workspace $workspace): WorkspacePlaytestActivity
    {
        $tally = $this->playtests->statusTallyForWorkspace($workspace);

        $byStatus = [];

        foreach (PlaytestStatus::cases() as $status) {
            $byStatus[$status->value] = $tally[$status->value] ?? 0;
        }

        return new WorkspacePlaytestActivity(
            playtestCount: $this->playtests->countForWorkspace($workspace),
            sessionCount: $this->playtests->countSessionsForWorkspace($workspace),
            playtestsByStatus: $byStatus,
            recentPlaytests: $this->playtests->recentForWorkspace($workspace, self::RECENT_PLAYTESTS),
        );
    }
}
