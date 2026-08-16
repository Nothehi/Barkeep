<?php

use Illuminate\Support\Facades\File;

/*
 * The scaffolder writes to `modules/`, which is where the real bounded
 * contexts live, and every test here removes what it created afterwards. That
 * arrangement is only safe while the name it scaffolds cannot collide with a
 * module somebody has actually built.
 *
 * It once did. These tests were written when Playtesting was still a plan, so
 * they used its name — and the day the module arrived, running the suite
 * deleted it.
 *
 * Hence the name below: a placeholder that reads as scaffolding rather than as
 * a domain, so nobody is ever tempted to build a bounded context called it.
 * The studly-case variant is derived from it for the same reason.
 */
$module = 'ScaffoldFixture';
$studlyInput = 'scaffold_fixture';

$layers = ['Domain', 'Application', 'Infrastructure', 'Presentation'];

afterEach(function () use ($module) {
    File::deleteDirectory(base_path("modules/{$module}"));
});

it('scaffolds every layer of a new module', function () use ($module, $layers) {
    $this->artisan('make:module', ['name' => $module])
        ->assertSuccessful();

    foreach ($layers as $layer) {
        expect(base_path("modules/{$module}/{$layer}"))->toBeDirectory()
            ->and(base_path("modules/{$module}/{$layer}/.gitkeep"))->toBeFile();
    }
});

it('normalises the module name to studly case', function () use ($module, $studlyInput) {
    $this->artisan('make:module', ['name' => $studlyInput])
        ->assertSuccessful();

    expect(base_path("modules/{$module}/Domain"))->toBeDirectory();
});

it('rejects module names that are not alphanumeric', function () {
    $this->artisan('make:module', ['name' => '../Escaped'])
        ->assertFailed();

    expect(base_path('modules/../Escaped'))->not->toBeDirectory();
});

it('refuses to touch an existing module without the force option', function () use ($module) {
    $this->artisan('make:module', ['name' => $module])->assertSuccessful();

    $this->artisan('make:module', ['name' => $module])->assertFailed();
});

it('restores missing layers of an existing module when forced', function () use ($module) {
    $this->artisan('make:module', ['name' => $module])->assertSuccessful();

    File::deleteDirectory(base_path("modules/{$module}/Infrastructure"));

    $this->artisan('make:module', ['name' => $module, '--force' => true])
        ->assertSuccessful();

    expect(base_path("modules/{$module}/Infrastructure/.gitkeep"))->toBeFile();
});

/**
 * The guard that stops this file from destroying somebody's work again.
 *
 * If a real module ever comes to be called what these tests scaffold, this
 * fails loudly *before* the `afterEach` above deletes it — which is exactly
 * the warning that was missing the first time.
 */
it('scaffolds under a name no real module uses', function () use ($module) {
    expect(File::exists(base_path("modules/{$module}/composer.json")))->toBeFalse()
        ->and(File::glob(base_path("modules/{$module}/**/*.php")))->toBe([]);
});
