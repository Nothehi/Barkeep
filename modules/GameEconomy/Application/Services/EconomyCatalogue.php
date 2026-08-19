<?php

namespace Modules\GameEconomy\Application\Services;

use Modules\GameEconomy\Domain\Exceptions\ActionDoesNotBelongToProfile;
use Modules\GameEconomy\Domain\Exceptions\ResourceDoesNotBelongToProfile;
use Modules\GameEconomy\Domain\Exceptions\VariableDoesNotBelongToScenario;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;

/**
 * The one place a record named in a request body is proved to belong here.
 *
 * Most identifiers in this module arrive as route segments and are resolved
 * through their parent by the router. Four do not, because there is no natural
 * URL for them:
 *
 * - the resource a flow moves,
 * - the resource a cost or a reward names,
 * - the resource or action a variable is about,
 * - the variable a scenario overrides.
 *
 * Every one of those is the invariant the database cannot express. A foreign key
 * proves the resource exists; only a lookup scoped by profile proves it belongs
 * to *this* configuration. So all four go through here, and the proof is
 * structural rather than a comparison: the record is resolved through the
 * profile, so one from a different configuration is never found rather than
 * being found and rejected.
 *
 * Every method has a `find` twin that returns null, used by validation — which
 * wants to report the problem next to the field rather than to raise it.
 */
final class EconomyCatalogue
{
    public function __construct(private readonly EconomyRepository $economy) {}

    /**
     * Resolve one of a configuration's resources, or fail.
     *
     * @throws ResourceDoesNotBelongToProfile
     */
    public function resourceOf(BalanceProfile $profile, string $resourceId): ResourceType
    {
        return $this->findResourceOf($profile, $resourceId)
            ?? throw ResourceDoesNotBelongToProfile::forPair($profile->getKey(), $resourceId);
    }

    /**
     * Resolve one of a configuration's resources, or return null.
     */
    public function findResourceOf(BalanceProfile $profile, string $resourceId): ?ResourceType
    {
        return $this->economy->findResourceInProfile($profile, $resourceId);
    }

    /**
     * Determine whether a resource id names one of this configuration's resources.
     */
    public function profileHasResource(BalanceProfile $profile, string $resourceId): bool
    {
        return $this->findResourceOf($profile, $resourceId) !== null;
    }

    /**
     * Resolve one of a configuration's actions, or fail.
     *
     * @throws ActionDoesNotBelongToProfile
     */
    public function actionOf(BalanceProfile $profile, string $actionId): EconomyAction
    {
        return $this->findActionOf($profile, $actionId)
            ?? throw ActionDoesNotBelongToProfile::forPair($profile->getKey(), $actionId);
    }

    /**
     * Resolve one of a configuration's actions, or return null.
     */
    public function findActionOf(BalanceProfile $profile, string $actionId): ?EconomyAction
    {
        return $this->economy->findActionInProfile($profile, $actionId);
    }

    /**
     * Determine whether an action id names one of this configuration's actions.
     */
    public function profileHasAction(BalanceProfile $profile, string $actionId): bool
    {
        return $this->findActionOf($profile, $actionId) !== null;
    }

    /**
     * Resolve the resource a cost, reward or flow names, through its action's
     * own profile.
     *
     * The overload that matters most in practice: a cost arrives naming an
     * action (from the route) and a resource (from the body), and the two are
     * only a valid pair if they share a configuration.
     *
     * @throws ResourceDoesNotBelongToProfile
     */
    public function resourceForAction(EconomyAction $action, string $resourceId): ResourceType
    {
        $profile = $action->profile;

        if ($profile === null) {
            throw ResourceDoesNotBelongToProfile::forPair($action->balance_profile_id, $resourceId);
        }

        return $this->resourceOf($profile, $resourceId);
    }

    /**
     * Resolve one of a scenario's own profile's variables, or fail.
     *
     * A scenario is a set of overrides on one configuration. Letting it name a
     * variable from a different profile would produce an override that changes
     * nothing anybody can see.
     *
     * @throws VariableDoesNotBelongToScenario
     */
    public function variableForScenario(BalanceScenario $scenario, string $variableId): BalanceVariable
    {
        return $this->findVariableForScenario($scenario, $variableId)
            ?? throw VariableDoesNotBelongToScenario::forPair($scenario->getKey(), $variableId);
    }

    /**
     * Resolve one of a scenario's own profile's variables, or return null.
     */
    public function findVariableForScenario(BalanceScenario $scenario, string $variableId): ?BalanceVariable
    {
        $profile = $scenario->profile;

        return $profile === null
            ? null
            : $this->economy->findVariableInProfile($profile, $variableId);
    }
}
