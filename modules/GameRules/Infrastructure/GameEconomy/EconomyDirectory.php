<?php

namespace Modules\GameRules\Infrastructure\GameEconomy;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Queries\GetActiveBalanceProfile;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\GameRules\Domain\ValueObjects\EconomyReference;

/**
 * The only file in GameRules that may import anything from GameEconomy.
 *
 * Sections 16, 34 and 46 of the module brief all reduce to one sentence: a rule
 * action says what a player may do, an economy action says what doing it costs,
 * and the rules must never hold a copy of the cost. This adapter is how the two
 * meet.
 *
 * What crosses the boundary is a `string` in each direction. A rule action, a
 * requirement and an effect each store a *handle* — `build`, `wood` — and this
 * turns a handle into an {@see EconomyReference}, which carries a label and an
 * already-worded summary and nothing that could be mistaken for a number this
 * module owns. Nothing here returns a GameEconomy model to a caller, and nothing
 * outside this file may reach for one.
 *
 * Three consequences follow, and all three are wanted:
 *
 * - the costs shown on the rules screen always agree with the balance screen,
 *   because there is only one set of them and they are read at render time;
 * - a handle that names nothing resolves to an unresolved reference rather than
 *   failing, which is the ordinary state of a rule set written before the
 *   economy was modelled — and of every rule set in a studio that never models
 *   one;
 * - `GameRules` can be read, tested and reasoned about with the whole of
 *   GameEconomy absent, because the integration is one class and it degrades to
 *   {@see nothing()}.
 *
 * The pattern is PrototypeIteration's: one adapter per foreign context, and an
 * architecture test holding the line. The amounts are formatted by the economy's
 * own values rather than by this module, because this module does not know that
 * amounts are exact decimals and must not learn.
 */
final class EconomyDirectory
{
    public function __construct(
        private readonly GetActiveBalanceProfile $activeProfile,
        private readonly EconomyRepository $economy,
    ) {}

    /**
     * Determine whether the design state has an economy to point at.
     *
     * What the interface asks before drawing an economy picker at all. Most
     * versions answer false, and that is not a problem to report.
     */
    public function hasEconomy(GameVersion $version): bool
    {
        return $this->activeProfile->handle($version) !== null;
    }

    /**
     * The economy actions a designer may wire a rule action to.
     *
     * Returns handles and labels, never models. Ordered by the economy's own
     * ordering, so the picker here reads the same way as the balance screen.
     *
     * @return list<array{handle: string, label: string}>
     */
    public function actionChoices(GameVersion $version): array
    {
        $profile = $this->activeProfile->handle($version);

        if ($profile === null) {
            return [];
        }

        $choices = [];

        foreach ($this->economy->actionsOf($profile) as $action) {
            $choices[] = ['handle' => $action->slug, 'label' => $action->name];
        }

        return $choices;
    }

    /**
     * The resources a designer may price a requirement or an effect in.
     *
     * @return list<array{handle: string, label: string}>
     */
    public function resourceChoices(GameVersion $version): array
    {
        $profile = $this->activeProfile->handle($version);

        if ($profile === null) {
            return [];
        }

        $choices = [];

        foreach ($this->economy->resourcesOf($profile) as $resource) {
            $choices[] = ['handle' => $resource->slug, 'label' => $resource->name];
        }

        return $choices;
    }

    /**
     * Resolve the economy action a rule action names.
     *
     * The summary is the sentence the action editor shows beside the rule —
     * "5 Wood, 2 Stone → 1 Building" — built from the economy's own records so
     * that it is never out of step with them. Null when the rule action names
     * nothing, which is the common case.
     */
    public function resolveAction(GameVersion $version, ?string $handle): ?EconomyReference
    {
        if ($handle === null || $handle === '') {
            return null;
        }

        $profile = $this->activeProfile->handle($version);

        if ($profile === null) {
            return EconomyReference::unresolved($handle);
        }

        $action = $this->economy->actionsWithEconomicsOf($profile)
            ->first(fn (EconomyAction $candidate): bool => $candidate->slug === $handle);

        if (! $action instanceof EconomyAction) {
            return EconomyReference::unresolved($handle);
        }

        return EconomyReference::resolved($handle, $action->name, $this->describe($action));
    }

    /**
     * Resolve the resource a requirement or an effect names.
     */
    public function resolveResource(GameVersion $version, ?string $handle): ?EconomyReference
    {
        if ($handle === null || $handle === '') {
            return null;
        }

        $profile = $this->activeProfile->handle($version);

        if ($profile === null) {
            return EconomyReference::unresolved($handle);
        }

        $resource = $this->economy->resourcesOf($profile)
            ->first(fn (ResourceType $candidate): bool => $candidate->slug === $handle);

        if (! $resource instanceof ResourceType) {
            return EconomyReference::unresolved($handle);
        }

        return EconomyReference::resolved(
            $handle,
            $resource->name,
            $resource->unit === null ? null : (string) __('Measured in :unit', ['unit' => $resource->unit]),
        );
    }

    /**
     * Determine whether every handle in the given list names something.
     *
     * What the validator asks. Handles that resolve to nothing are reported as
     * warnings rather than errors, and only when the version has an economy at
     * all — telling a studio that has not modelled one that all forty of their
     * references are broken would be noise, not a finding.
     *
     * @param  list<string>  $actionHandles
     * @param  list<string>  $resourceHandles
     * @return list<string> the handles that name nothing
     */
    public function unresolvedHandles(GameVersion $version, array $actionHandles, array $resourceHandles): array
    {
        $profile = $this->activeProfile->handle($version);

        if ($profile === null) {
            return [];
        }

        $knownActions = $this->economy->actionsOf($profile)->pluck('slug')->all();
        $knownResources = $this->economy->resourcesOf($profile)->pluck('slug')->all();

        return array_values(array_unique([
            ...array_diff(array_filter($actionHandles), $knownActions),
            ...array_diff(array_filter($resourceHandles), $knownResources),
        ]));
    }

    /**
     * An exact decimal, without the trailing zeros the column stores it with.
     *
     * `decimal(20, 6)` means one crew comes back as `1.000000`, and "1.000000
     * Crew, 2.000000 Stone" is not a sentence anybody wants beside a rule. Done
     * by trimming the string rather than by casting: parsing to a float to
     * format it would undo the exactness the economy's whole `Quantity` type
     * exists to protect, in the one place this module touches its numbers.
     */
    private function readable(string $amount): string
    {
        if (! str_contains($amount, '.')) {
            return $amount;
        }

        return rtrim(rtrim($amount, '0'), '.');
    }

    /**
     * Word what an economy action moves, in one line.
     *
     * The amounts come from the economy's own `Quantity` values through their
     * string form. This module never parses one — it has no arithmetic to do and
     * turning an exact decimal into a float here would undo the reason
     * GameEconomy stores them the way it does.
     */
    private function describe(EconomyAction $action): ?string
    {
        $costs = $action->costs
            ->map(fn (ActionCost $cost): string => trim($this->readable($cost->amount->value).' '.$cost->resource->name))
            ->filter()
            ->all();

        $rewards = $action->rewards
            ->map(fn (ActionReward $reward): string => trim($this->readable($reward->amount->value).' '.$reward->resource->name))
            ->filter()
            ->all();

        if ($costs === [] && $rewards === []) {
            return null;
        }

        if ($rewards === []) {
            return (string) __('Costs :costs', ['costs' => implode(', ', $costs)]);
        }

        if ($costs === []) {
            return (string) __('Pays :rewards', ['rewards' => implode(', ', $rewards)]);
        }

        return (string) __(':costs → :rewards', [
            'costs' => implode(', ', $costs),
            'rewards' => implode(', ', $rewards),
        ]);
    }
}
