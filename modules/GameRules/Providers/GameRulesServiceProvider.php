<?php

namespace Modules\GameRules\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Queries\GetRuleSet;
use Modules\GameRules\Domain\Exceptions\RuleSystemViolation;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Domain\Policies\RuleSetPolicy;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Wires the GameRules bounded context into the application.
 *
 * This module owns the formal rule system of a game — what players may do, when,
 * under what conditions and to what effect — and everything that decides how one
 * of those records is found, who may touch it and how its rules surface over HTTP
 * is configured here rather than being spread across the application's own
 * providers.
 */
class GameRulesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the module.
     */
    public function boot(): void
    {
        $this->configureRouteBindings();
        $this->configureAuthorization();
        $this->configureExceptionRendering();
    }

    /**
     * Teach the router how to find everything the module addresses.
     *
     * Sixteen bindings, each resolving a record *through* the one above it. The
     * game version was already resolved through a game by GameDesign's own
     * binding, and the game through a workspace, so the whole ownership chain —
     *
     *     workspace → game → version → rule set → rule | mechanic | phase | …
     *                                           → rule  → reference
     *                                           → group → membership
     *
     * — is walked by the router before any handler or policy runs.
     *
     * The security property that buys is worth stating plainly. There is no route
     * in this module on which an identifier is looked up without its parent. A
     * phase belonging to somebody else's rule system does not 403; it fails to
     * resolve, and the request 404s before a controller sees it. That is why the
     * module's ids can be opaque uuids in a URL without any of them being a
     * capability.
     *
     * ## The parameter names
     *
     * Route binder names are global to the application — Laravel keeps one binder
     * per parameter name, and the provider registered last silently wins. Seven
     * names here were chosen with that in mind, and every one of them is longer
     * than it would naturally be:
     *
     * - `{version}` is *not* bound here. GameDesign already resolves a game's
     *   design state under that name, which is exactly what this module needs;
     *   claiming it again would break GameDesign's own chain and DesignFramework's
     *   delegation with it. See `.ai/rules/providers.md`.
     * - `{gameRule}` rather than `{rule}`, which is far too generic to claim
     *   globally in an application with a validation facade of the same name.
     * - `{gamePhase}` rather than `{phase}`, because `{phase}` belongs to
     *   DesignFramework — a stage of the *designer's* work rather than of play.
     * - `{ruleMechanic}` rather than `{mechanic}`, because `{mechanic}` belongs to
     *   GameDesign's shared design vocabulary.
     * - `{ruleAction}` rather than `{action}`, the most overloaded word in the
     *   application.
     * - `{ruleEffect}` rather than `{effect}`, which GameEconomy binds.
     * - `{ruleCondition}` rather than `{condition}`, for the same reason
     *   `{gameRule}` is not `{rule}`.
     *
     * Explicit bindings run in the order the parameters appear in the URL, and the
     * URLs are nested in the same order as the chain — which is what makes "the
     * parent is already a model by the time the child resolves" true rather than
     * hopeful.
     */
    private function configureRouteBindings(): void
    {
        Route::bind('ruleSet', function (string $value, RouteInstance $route): RuleSet {
            $version = $route->parameter('version');

            $ruleSet = $version instanceof GameVersion && Str::isUuid($value)
                ? $this->app->make(GetRuleSet::class)->handle($version, $value)
                : null;

            return $ruleSet ?? throw (new ModelNotFoundException)->setModel(RuleSet::class, [$value]);
        });

        Route::bind('gameRule', fn (string $value, RouteInstance $route): GameRule => $this->resolveInRuleSet(
            $route,
            $value,
            GameRule::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?GameRule => $structure
                ->findRuleInRuleSet($ruleSet, $value),
        ));

        Route::bind('ruleMechanic', fn (string $value, RouteInstance $route): RuleMechanic => $this->resolveInRuleSet(
            $route,
            $value,
            RuleMechanic::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleMechanic => $structure
                ->findMechanicInRuleSet($ruleSet, $value),
        ));

        Route::bind('gamePhase', fn (string $value, RouteInstance $route): GamePhase => $this->resolveInRuleSet(
            $route,
            $value,
            GamePhase::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?GamePhase => $structure
                ->findPhaseInRuleSet($ruleSet, $value),
        ));

        Route::bind('transition', fn (string $value, RouteInstance $route): PhaseTransition => $this->resolveInRuleSet(
            $route,
            $value,
            PhaseTransition::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?PhaseTransition => $structure
                ->findTransitionInRuleSet($ruleSet, $value),
        ));

        Route::bind('ruleAction', fn (string $value, RouteInstance $route): RuleAction => $this->resolveInRuleSet(
            $route,
            $value,
            RuleAction::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleAction => $structure
                ->findActionInRuleSet($ruleSet, $value),
        ));

        Route::bind('requirement', fn (string $value, RouteInstance $route): RuleRequirement => $this->resolveInRuleSet(
            $route,
            $value,
            RuleRequirement::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleRequirement => $structure
                ->findRequirementInRuleSet($ruleSet, $value),
        ));

        Route::bind('ruleCondition', fn (string $value, RouteInstance $route): RuleCondition => $this->resolveInRuleSet(
            $route,
            $value,
            RuleCondition::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleCondition => $structure
                ->findConditionInRuleSet($ruleSet, $value),
        ));

        Route::bind('conditionGroup', fn (string $value, RouteInstance $route): ConditionGroup => $this->resolveInRuleSet(
            $route,
            $value,
            ConditionGroup::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?ConditionGroup => $structure
                ->findConditionGroupInRuleSet($ruleSet, $value),
        ));

        Route::bind('ruleEffect', fn (string $value, RouteInstance $route): RuleEffect => $this->resolveInRuleSet(
            $route,
            $value,
            RuleEffect::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleEffect => $structure
                ->findEffectInRuleSet($ruleSet, $value),
        ));

        Route::bind('trigger', fn (string $value, RouteInstance $route): RuleTrigger => $this->resolveInRuleSet(
            $route,
            $value,
            RuleTrigger::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?RuleTrigger => $structure
                ->findTriggerInRuleSet($ruleSet, $value),
        ));

        Route::bind('victoryCondition', fn (string $value, RouteInstance $route): VictoryCondition => $this->resolveInRuleSet(
            $route,
            $value,
            VictoryCondition::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?VictoryCondition => $structure
                ->findVictoryConditionInRuleSet($ruleSet, $value),
        ));

        Route::bind('defeatCondition', fn (string $value, RouteInstance $route): DefeatCondition => $this->resolveInRuleSet(
            $route,
            $value,
            DefeatCondition::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?DefeatCondition => $structure
                ->findDefeatConditionInRuleSet($ruleSet, $value),
        ));

        Route::bind('endCondition', fn (string $value, RouteInstance $route): GameEndCondition => $this->resolveInRuleSet(
            $route,
            $value,
            GameEndCondition::class,
            fn (RuleStructureRepository $structure, RuleSet $ruleSet): ?GameEndCondition => $structure
                ->findEndConditionInRuleSet($ruleSet, $value),
        ));

        /*
         * The two bindings that resolve through something other than the rule set,
         * because the record only means anything inside a narrower scope.
         */
        Route::bind('reference', function (string $value, RouteInstance $route): RuleReference {
            $ruleSet = $route->parameter('ruleSet');

            $reference = $ruleSet instanceof RuleSet && Str::isUuid($value)
                ? $this->app->make(RuleStructureRepository::class)->findReferenceInRuleSet($ruleSet, $value)
                : null;

            return $reference ?? throw (new ModelNotFoundException)->setModel(RuleReference::class, [$value]);
        });

        Route::bind('membership', function (string $value, RouteInstance $route): ConditionGroupCondition {
            $group = $route->parameter('conditionGroup');

            $membership = $group instanceof ConditionGroup && Str::isUuid($value)
                ? $this->app->make(RuleStructureRepository::class)->findMembership($group, $value)
                : null;

            return $membership ?? throw (new ModelNotFoundException)->setModel(ConditionGroupCondition::class, [$value]);
        });
    }

    /**
     * Resolve one of a rule system's records, or 404.
     *
     * Thirteen bindings differ only in which lookup they call, so they share this.
     * The uuid check in front of the query is not cosmetic: PostgreSQL raises
     * rather than returning nothing when a uuid column is compared against a
     * string that is not one, which would turn a mistyped URL into a 500.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $model
     * @param  callable(RuleStructureRepository, RuleSet): ?TModel  $lookup
     * @return TModel
     */
    private function resolveInRuleSet(RouteInstance $route, string $value, string $model, callable $lookup)
    {
        $ruleSet = $route->parameter('ruleSet');

        $resolved = $ruleSet instanceof RuleSet && Str::isUuid($value)
            ? $lookup($this->app->make(RuleStructureRepository::class), $ruleSet)
            : null;

        return $resolved ?? throw (new ModelNotFoundException)->setModel($model, [$value]);
    }

    /**
     * Point the gate at the module's policy.
     *
     * One policy for sixteen models, which is deliberate. A rule, a mechanic, a
     * phase, a transition, an action, a requirement, a condition, a group, a
     * membership, an effect, a trigger, three kinds of outcome and a reference
     * have no permissions of their own — what decides whether any of them may be
     * touched is whether the rule set around them is still a draft, and that is
     * one question with one answer. Giving each its own policy would be fifteen
     * copies of it.
     */
    private function configureAuthorization(): void
    {
        Gate::policy(RuleSet::class, RuleSetPolicy::class);
    }

    /**
     * Turn the module's domain rules into HTTP responses.
     *
     * Registered as one renderer for the whole family rather than as a catch at
     * each call site, so a rule added later surfaces correctly without any
     * controller having to know about it.
     *
     * A violation that names a field is reported as a validation error, which is
     * what puts "that phase belongs to a different rule set" next to the phase
     * picker instead of in a toast — and what puts "that would put this rule
     * inside itself" beside the parent selector the designer just used.
     */
    private function configureExceptionRendering(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (RuleSystemViolation $violation, Request $request) {
                $field = $violation->field();

                /*
                 * A violation that names a field becomes a validation error for
                 * both surfaces, which is where this module differs from
                 * GameEconomy's otherwise identical renderer. Laravel already
                 * knows how to word a `ValidationException` as an Inertia
                 * redirect-with-errors *and* as a JSON `errors` envelope, so
                 * saying it once gets both — and a client calling the API gets
                 * "that phase belongs to a different rule set" attached to
                 * `phase_id` rather than as prose it would have to parse.
                 */
                if ($field !== null) {
                    throw ValidationException::withMessages([$field => $violation->getMessage()]);
                }

                if ($request->expectsJson()) {
                    return response()->json(
                        ['message' => $violation->getMessage()],
                        $violation->status(),
                    );
                }

                return back()->withErrors(['rules' => $violation->getMessage()]);
            });
        });
    }
}
