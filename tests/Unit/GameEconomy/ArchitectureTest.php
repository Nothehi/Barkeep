<?php

/**
 * GameEconomy sits on top of GameDesign and is a sibling of Playtesting,
 * DesignFramework and PrototypeIteration: it models the quantitative systems
 * that need balancing, where those record evidence, method and work.
 *
 * It is also a module everything built next will want to read — analytics wants
 * to count profiles, AI wants to suggest a value, gamification wants to notice a
 * snapshot. These tests keep every one of those relationships one-directional.
 */
$laterContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Gamification',
    'Modules\Knowledge',
    'Modules\Moderation',
];

arch('game economy does not depend on any context built on top of it')
    ->expect('Modules\GameEconomy')
    ->not->toUse($laterContexts);

/**
 * The three siblings. Playtesting produces evidence, DesignFramework describes
 * method, PrototypeIteration records the work, and this module models the
 * numbers. None imports another.
 *
 * The Playtesting line is the one worth being explicit about. A balance
 * observation is the *interpretation* of evidence rather than the evidence, and
 * the moment this module can read a playtest it will end up holding a copy of
 * one — see `BalanceObservation`, whose `source_reference` is a plain string for
 * exactly this reason.
 */
arch('game economy and its sibling contexts do not know about each other')
    ->expect('Modules\GameEconomy')
    ->not->toUse([
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
    ]);

/**
 * The module may talk to Identity, Workspace and GameDesign, but only through
 * the parts they publish: their domain models and value objects, their
 * application commands, queries and services, and their resources for rendering.
 * Reaching into any of their infrastructure or controllers would couple the
 * modules through their plumbing rather than through their language.
 */
arch('game economy does not reach into another context\'s internals')
    ->expect('Modules\GameEconomy')
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
 * The tenancy rules have one home, and it is not this one.
 *
 * "May this person tune this game's economy?" already accounts for workspace
 * membership, the workspace's status and the game's own — GameDesign's policy
 * works all of that out, and asking the same questions again here would be a
 * second implementation that could disagree with the first. So nothing in this
 * module knows what a workspace role is.
 */
arch('nothing in game economy knows what a workspace role is')
    ->expect([
        'Modules\Workspace\Domain\Enums\WorkspaceRole',
        'Modules\Workspace\Domain\Models\WorkspaceMember',
    ])
    ->not->toBeUsedIn('Modules\GameEconomy');

/**
 * Reading a game's design versions happens in one adapter, so the
 * game/version half of the foundational invariant has a single enforcement point
 * rather than being re-derived wherever somebody needs a version.
 */
arch('only the game adapter reaches for GameDesign\'s commands and queries')
    ->expect([
        'Modules\GameDesign\Application\Commands',
        'Modules\GameDesign\Application\Queries',
    ])
    ->not->toBeUsedIn([
        'Modules\GameEconomy\Domain',
        'Modules\GameEconomy\Presentation',
        'Modules\GameEconomy\Infrastructure\Persistence',
        'Modules\GameEconomy\Infrastructure\Calculations',
        'Modules\GameEconomy\Infrastructure\Authorization',
    ]);

/**
 * The calculations are pure arithmetic over records somebody else loaded. They
 * are also the part of the module a studio's trust rests on, so they must be
 * testable without a request and — more importantly — incapable of writing.
 */
arch('the calculation layer knows nothing about delivery')
    ->expect('Modules\GameEconomy\Infrastructure\Calculations')
    ->not->toUse([
        'Modules\GameEconomy\Presentation',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\GameEconomy\Domain')
    ->not->toUse([
        'Modules\GameEconomy\Application',
        'Modules\GameEconomy\Presentation',
        'Modules\GameEconomy\Providers',
    ]);

/**
 * The models are Eloquent active records and the policy answers the framework
 * gate, so both knowingly touch Illuminate. The rest of the domain has no such
 * excuse: it is where the rules live, and rules should be testable without a
 * database or a request.
 *
 * The value objects matter most here. `Quantity` is the reason every number in
 * this module is exact, and it must stay a thing anybody can reason about
 * without booting an application.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\GameEconomy\Domain\Enums',
        'Modules\GameEconomy\Domain\Events',
        'Modules\GameEconomy\Domain\Exceptions',
        'Modules\GameEconomy\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\GameEconomy\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\GameEconomy\Application')
    ->not->toUse([
        'Modules\GameEconomy\Presentation',
        'Inertia\Inertia',
    ]);

/**
 * The events are the module's published surface for everything built next, so a
 * consumer must never be handed something it can quietly mutate.
 */
arch('domain events are immutable value objects')
    ->expect('Modules\GameEconomy\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects carry no persistence concerns')
    ->expect('Modules\GameEconomy\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands, queries, DTOs and services are final')
    ->expect([
        'Modules\GameEconomy\Application\Commands',
        'Modules\GameEconomy\Application\Queries',
        'Modules\GameEconomy\Application\DTOs',
        'Modules\GameEconomy\Application\Services',
    ])
    ->classes()
    ->toBeFinal();

arch('the adapters and calculators are final')
    ->expect([
        'Modules\GameEconomy\Infrastructure\GameDesign',
        'Modules\GameEconomy\Infrastructure\Authorization',
        'Modules\GameEconomy\Infrastructure\Calculations',
        'Modules\GameEconomy\Infrastructure\Persistence\Repositories',
    ])
    ->classes()
    ->toBeFinal();

/**
 * Nothing outside the module gets to touch a balance table directly. Later
 * contexts react to events; the moment Analytics writes its own
 * `BalanceProfile::where(...)`, the ownership-chain scoping has two homes.
 */
arch('nothing outside game economy reaches for its models or repositories')
    ->expect([
        'Modules\GameEconomy\Domain\Models',
        'Modules\GameEconomy\Infrastructure\Persistence',
    ])
    ->not->toBeUsedIn([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
        'App\Http\Middleware',
    ]);

/**
 * The inverse of the first test, checked from the other side. The six modules
 * GameEconomy sits beside or above must not have learned that it exists — which
 * is what keeps them buildable, testable and extractable without it.
 */
arch('the other contexts have not learned about game economy')
    ->expect([
        'Modules\Identity',
        'Modules\Workspace',
        'Modules\GameDesign',
        'Modules\Playtesting',
        'Modules\DesignFramework',
        'Modules\PrototypeIteration',
    ])
    ->not->toUse('Modules\GameEconomy');
