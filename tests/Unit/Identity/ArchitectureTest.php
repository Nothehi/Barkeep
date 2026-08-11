<?php

/**
 * Identity is the foundation every other bounded context builds on, so the
 * dependency has to point one way only. These tests fail the moment Identity
 * starts reaching into a context that should be reaching into it.
 */
$otherContexts = [
    'Modules\Administration',
    'Modules\Analytics',
    'Modules\Community',
    'Modules\Content',
    'Modules\Framework',
    'Modules\GameDesign',
    'Modules\Gamification',
    'Modules\Moderation',
    'Modules\Playtesting',
    'Modules\Workspace',
];

arch('identity does not depend on any other bounded context')
    ->expect('Modules\Identity')
    ->not->toUse($otherContexts);

arch('the domain layer stays free of the layers above it')
    ->expect('Modules\Identity\Domain')
    ->not->toUse([
        'Modules\Identity\Application',
        'Modules\Identity\Presentation',
        'Modules\Identity\Providers',
    ]);

/**
 * The User model is an Eloquent active record: it knowingly touches the
 * framework, Fortify's authenticatable traits and its own factory. Everything
 * else in the domain has no such excuse.
 */
arch('the rest of the domain is free of the framework and of infrastructure')
    ->expect([
        'Modules\Identity\Domain\Enums',
        'Modules\Identity\Domain\Events',
        'Modules\Identity\Domain\Exceptions',
        'Modules\Identity\Domain\ValueObjects',
    ])
    ->not->toUse([
        'Modules\Identity\Infrastructure',
        'Illuminate\Database',
        'Illuminate\Http',
        'Inertia\Inertia',
        'Laravel\Fortify',
    ]);

arch('the application layer does not depend on delivery concerns')
    ->expect('Modules\Identity\Application')
    ->not->toUse([
        'Modules\Identity\Presentation',
        'Inertia\Inertia',
        'Laravel\Fortify',
    ]);

arch('domain events are immutable value objects')
    ->expect('Modules\Identity\Domain\Events')
    ->toBeFinal()
    ->toBeReadonly();

arch('value objects and enums carry no persistence concerns')
    ->expect('Modules\Identity\Domain\ValueObjects')
    ->not->toUse('Illuminate\Database');

arch('application commands and queries are final')
    ->expect('Modules\Identity\Application')
    ->classes()
    ->toBeFinal();

arch('nothing outside identity reaches for identity internals')
    ->expect(['Modules\Identity\Domain\Models', 'Modules\Identity\Infrastructure'])
    ->not->toBeUsedIn('App\Http\Middleware');
