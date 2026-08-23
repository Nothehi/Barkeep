<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web\Concerns;

use Illuminate\Http\Request;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\GameEconomy\EconomyDirectory;
use Modules\GameRules\Presentation\Http\Resources\RuleSetResource;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The props every rules screen starts from.
 *
 * Five of them, and every screen in the module needs all five: the workspace, the
 * game, the design version, the rule set and the vocabulary. Assembling them once
 * rather than in each of the eight controllers is what stops one screen from
 * quietly shipping a rule set without its permissions map and rendering every
 * button disabled.
 *
 * The economy props are the interesting part. `economy` says whether the design
 * version has an active balance profile at all, and carries the handles a designer
 * may wire an action or an effect to. It is read through the one adapter allowed
 * to reach GameEconomy, and it is empty for most rule sets — a studio that has not
 * modelled an economy simply gets no economy pickers, rather than a form full of
 * broken references.
 */
trait RendersRuleScreens
{
    use ProvidesRuleVocabulary;

    /**
     * The chrome every rules screen renders around whatever it is showing.
     *
     * @return array<string, mixed>
     */
    protected function ruleScreenProps(
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        ?RuleSet $ruleSet = null,
    ): array {
        return [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'ruleSet' => $ruleSet === null
                ? null
                : RuleSetResource::make($ruleSet->load(['version', 'creator'])),
            'options' => $this->ruleVocabulary(),
        ];
    }

    /**
     * What the game's economy offers a rule to point at.
     *
     * Handles and labels only. Nothing here is a cost — see section 34 of the
     * module brief and `EconomyDirectory`.
     *
     * @return array{available: bool, actions: list<array{handle: string, label: string}>, resources: list<array{handle: string, label: string}>}
     */
    protected function economyProps(GameVersion $version): array
    {
        $economy = app(EconomyDirectory::class);

        if (! $economy->hasEconomy($version)) {
            return ['available' => false, 'actions' => [], 'resources' => []];
        }

        return [
            'available' => true,
            'actions' => $economy->actionChoices($version),
            'resources' => $economy->resourceChoices($version),
        ];
    }

    /**
     * Whether the caller may start a rule system for this design state.
     */
    protected function canCreateRuleSet(Request $request, GameVersion $version): bool
    {
        $user = $request->user();

        return $user instanceof User
            && $request->user()?->can('create', [RuleSet::class, $version]) === true;
    }
}
