<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DesignFrameworkSeeder;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/*
|--------------------------------------------------------------------------
| Seeding a production install
|--------------------------------------------------------------------------
|
| `MechanicSeeder` and `DesignFrameworkSeeder` are content and are meant to run
| on every install, including one built with `composer install --no-dev`. There
| is no `fakerphp/faker` there, and Laravel only defines `fake()` when Faker is
| present — so a model factory anywhere on the unguarded seed path aborts the
| whole `db:seed` with an undefined function, before any content is written.
|
| That is what these tests hold: the content path creates its rows without a
| factory, and the throwaway login that does need one stays behind the local
| guard with the sample data.
|
*/

it('seeds the content without creating the throwaway login outside local', function () {
    expect(app()->environment('local'))->toBeFalse();

    $this->seed(DatabaseSeeder::class);

    expect(Mechanic::query()->count())->toBeGreaterThan(0)
        ->and(Framework::query()->count())->toBe(1)
        ->and(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

it('attributes the framework to an account it creates on an empty database', function () {
    expect(User::query()->count())->toBe(0);

    $this->seed(DesignFrameworkSeeder::class);

    $framework = Framework::query()->sole();
    $author = User::query()->sole();

    expect($framework->created_by)->toBe($author->id)
        ->and($author->email)->toBe('framework@barkeep.test')
        ->and($author->name)->toBe('Barkeep');
});

it('keeps model factories off the unguarded seed path', function (string $seeder) {
    $source = file_get_contents(database_path('seeders/'.$seeder.'.php'));

    expect($source)
        ->not->toContain('::factory(')
        ->not->toContain('fake()');
})->with([
    'DatabaseSeeder path outside the local guard' => 'MechanicSeeder',
    'the framework content' => 'DesignFrameworkSeeder',
    'the Persian edition' => 'FaDesignFrameworkSeeder',
]);
