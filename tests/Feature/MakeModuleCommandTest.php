<?php

use Illuminate\Support\Facades\File;

$layers = ['Domain', 'Application', 'Infrastructure', 'Presentation'];

afterEach(function () {
    File::deleteDirectory(base_path('modules/Playtesting'));
    File::deleteDirectory(base_path('modules/PlayTesting'));
});

it('scaffolds every layer of a new module', function () use ($layers) {
    $this->artisan('make:module', ['name' => 'Playtesting'])
        ->assertSuccessful();

    foreach ($layers as $layer) {
        expect(base_path("modules/Playtesting/{$layer}"))->toBeDirectory()
            ->and(base_path("modules/Playtesting/{$layer}/.gitkeep"))->toBeFile();
    }
});

it('normalises the module name to studly case', function () {
    $this->artisan('make:module', ['name' => 'play_testing'])
        ->assertSuccessful();

    expect(base_path('modules/PlayTesting/Domain'))->toBeDirectory();
});

it('rejects module names that are not alphanumeric', function () {
    $this->artisan('make:module', ['name' => '../Escaped'])
        ->assertFailed();

    expect(base_path('modules/../Escaped'))->not->toBeDirectory();
});

it('refuses to touch an existing module without the force option', function () {
    $this->artisan('make:module', ['name' => 'Playtesting'])->assertSuccessful();

    $this->artisan('make:module', ['name' => 'Playtesting'])->assertFailed();
});

it('restores missing layers of an existing module when forced', function () {
    $this->artisan('make:module', ['name' => 'Playtesting'])->assertSuccessful();

    File::deleteDirectory(base_path('modules/Playtesting/Infrastructure'));

    $this->artisan('make:module', ['name' => 'Playtesting', '--force' => true])
        ->assertSuccessful();

    expect(base_path('modules/Playtesting/Infrastructure/.gitkeep'))->toBeFile();
});
