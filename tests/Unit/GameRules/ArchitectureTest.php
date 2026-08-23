<?php

/**
 * GameRules sits on top of GameDesign and reads GameEconomy through exactly one
 * adapter. It is the last module in `bootstrap/providers.php`, and these tests
 * are what keep that position honest: everything below it must remain buildable,
 * testable and extractable without it.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Gamification',
    'Modules\Knowledge',
    'Modules\Moderation',
    'Modules\GameRuntime',
];

arch('game rules does not depend on any context built on top of it')
    ->expect('Modules\GameRules')
    ->not->toUse($laterContexts);

/**
 * The three siblings it must not learn about.
 *
 * Playtesting produces evidence, DesignFramework describes method and
 * PrototypeIteration records the work. Section 36 of the module brief is explicit
 * that evidence reaches this module through a future contract rather than by
 * import — the moment a rule can read a playtest, the rules screen ends up
 * holding a copy of an observation.
 */
arch('game rules and its sibling contexts do not know about each other')
    ->expect('Modules\GameRules')
    ->not->toUse([
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
    ]);

/**
 * The module may talk to Identity, Workspace and GameDesign, but only through the
 * parts they publish: their domain models and value objects, their application
 * commands, queries and services, and their resources for rendering. Reaching
 * into any of their infrastructure or controllers would couple the modules
 * through their plumbing rather than through their language.
 */
arch('game rules does not reach into another context\'s internals')
    ->expect('Modules\GameRules')
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Modules\Identity\Presentation\Http\Controllers',
        'Modules\Identity\Presentation\Http\Requests',
        'Modules\Workspace\Infrastructure',
        'Modules\Workspace\Presentation\Http\Controllers',
        'Modules\Workspace\Presentation\Http\Requests',
        'Modules\GameDesign\Infrastructure',
        'Modules\GameDesign\Presentation\Http\Controllers',
        'Modules\GameDesign\Presentation\Http\Requests',
    ]);

/**
 * The GameEconomy boundary, and the most load-bearing test in this file.
 *
 * Sections 16, 34 and 46 of the brief all reduce to one rule: a rule action says
 * what a player may do, an economy action says what doing it costs, and this
 * module must never hold a copy of the cost. `EconomyDirectory` is the single
 * seam — it turns a handle into a value object and returns nothing that could be
 * mistaken for a number this module owns.
 *
 * The way this breaks is a convenient `with('costs')` in a repository or an
 * `exists:resource_types` rule in a validator. Route both through the adapter
 * instead.
 */
arch('only one file in game rules may read the economy')
    ->expect('Modules\GameEconomy')
    ->not->toBeUsedIn([
        'Modules\GameRules\Domain',
        'Modules\GameRules\Application',
        'Modules\GameRules\Presentation',
        'Modules\GameRules\Providers',
        'Modules\GameRules\Infrastructure\Analysis',
        'Modules\GameRules\Infrastructure\Authorization',
        'Modules\GameRules\Infrastructure\GameDesign',
        'Modules\GameRules\Infrastructure\Persistence',
    ]);

/**
 * The tenancy rules have one home, and it is not this one.
 *
 * "May this person write this game's rules?" already accounts for workspace
 * membership, the workspace's status and the game's own — GameDesign's policy
 * works all of that out, and asking the same questions again here would be a
 * second implementation that could disagree with the first.
 */
arch('nothing in game rules knows what a workspace role is')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn('Modules\GameRules');

/**
 * Reading a game's design versions happens in one adapter, so the game/version
 * half of the foundational invariant has a single enforcement point rather than
 * being re-derived wherever somebody needs a version.
 */
arch('only the game adapter reaches for GameDesign\'s commands and queries')
    ->expect([
        'Modules\GameDesign\Application\Commands',
        'Modules\GameDesign\Application\Queries',
    ])
    ->not->toBeUsedIn([
        'Modules\GameRules\Domain',
        'Modules\GameRules\Presentation',
        'Modules\GameRules\Infrastructure\Persistence',
        'Modules\GameRules\Infrastructure\Analysis',
        'Modules\GameRules\Infrastructure\Authorization',
    ]);

/**
 * The analysis layer is the part of the module a studio's trust rests on, so it
 * must be testable without a request and — more importantly — incapable of
 * writing. The validator reports; it never fixes.
 */
arch('the analysis layer knows nothing about delivery')
    ->expect('Modules\GameRules\Infrastructure\Analysis')
    ->not->toUse([
        'Modules\GameRules\Presentation',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\GameRules\Domain')
    ->not->toUse([
        'Modules\GameRules\Application',
        'Modules\GameRules\Presentation',
        'Modules\GameRules\Providers',
    ]);

/**
 * The models are Eloquent active records and the policy answers the framework
 * gate, so both knowingly touch Illuminate. The rest of the domain has no such
 * excuse: it is where the rules live, and rules should be testable without a
 * database or a request.
 *
 * `CycleDetector` is deliberately *not* in here — it lives in Infrastructure
 * because it is an algorithm rather than a rule, and it is already framework-free.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\GameRules\Domain\Enums',
        'Modules\GameRules\Domain\Events',
        'Modules\GameRules\Domain\Exceptions',
        'Modules\GameRules\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\GameRules\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\GameRules\Application')
    ->not->toUse([
        'Modules\GameRules\Presentation',
        'Inertia\Inertia',
    ]);

/**
 * The events are the module's published surface for everything built next — the
 * runtime engine most of all — so a consumer must never be handed something it
 * can quietly mutate.
 */
arch('domain events are immutable value objects')
    ->expect('Modules\GameRules\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\GameRules\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries, DTOs and services are final')
    ->expect([
        'Modules\GameRules\Application\Commands',
        'Modules\GameRules\Application\Queries',
        'Modules\GameRules\Application\DTOs',
        'Modules\GameRules\Application\Services',
        'Modules\GameRules\Application\Validation',
    ])
    ->classes()
    ->toBeFinal();

arch('the adapters, analysers and repositories are final')
    ->expect([
        'Modules\GameRules\Infrastructure\GameDesign',
        'Modules\GameRules\Infrastructure\GameEconomy',
        'Modules\GameRules\Infrastructure\Authorization',
        'Modules\GameRules\Infrastructure\Analysis',
        'Modules\GameRules\Infrastructure\Persistence\Repositories',
    ])
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a rules table directly. Later contexts
 * react to events; the moment a runtime engine writes its own
 * `GameRule::where(...)`, the ownership-chain scoping has two homes.
 */
arch('nothing outside game rules reaches for its models or repositories')
    ->expect([
        'Modules\GameRules\Domain\Models',
        'Modules\GameRules\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
        'Modules\GameEconomy',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the first test, checked from the other side. The seven modules
 * GameRules sits beside or above must not have learned that it exists.
 */
arch('the other contexts have not learned about game rules')
    ->expect([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
        'Modules\GameEconomy',
    ])
    ->not->toUse('Modules\GameRules');

/**
 * Section 33 of the brief, as a test.
 *
 * This module describes a board game and never plays one. There is no scheduler,
 * no queue worker and no console command in it, because every one of those is a
 * way for something to start *running* the rules — and a runtime engine living
 * inside a design tool is the failure mode the whole module is shaped to avoid.
 */
arch('game rules never schedules or queues anything')
    ->expect('Modules\GameRules')
    ->not->toUse([
        'Illuminate\Contracts\Queue\ShouldQueue',
        'Illuminate\Console\Command',
        'Illuminate\Support\Facades\Schedule',
        'Illuminate\Support\Facades\Artisan',
    ]);
