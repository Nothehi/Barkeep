<?php

use Illuminate\Support\Facades\Event;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkCreated;
use Modules\DesignFramework\Domain\Events\FrameworkPublished;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\Identity\Domain\Models\User;

beforeEach(function () {
    $this->author = User::factory()->create(['email' => 'author@barkeep.test']);
    $this->designer = User::factory()->create();

    /*
     * Framework administration is platform-wide rather than workspace-shaped, and until the
     * Administration context exists the set of accounts allowed to write methodology is a
     * configuration list. Naming it here is the test doing what a deployment does.
     */
    config()->set('design-framework.administrators', ['author@barkeep.test']);
});

function createFramework(array $payload = [])
{
    return test()->actingAs(test()->author)->postJson('/api/v1/frameworks', array_merge([
        'name' => 'Board Game Design Framework',
    ], $payload));
}

it('starts a methodology as a draft with no versions', function () {
    createFramework(['description' => 'Ten stages from idea to game.'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Board Game Design Framework')
        ->assertJsonPath('data.description', 'Ten stages from idea to game.')
        ->assertJsonPath('data.status', FrameworkStatus::Draft->value)
        ->assertJsonPath('data.versions_count', 0);
});

/**
 * A framework has no workspace, and that absence is the point: a methodology is something Barkeep
 * publishes and studios adopt, not a document inside one.
 */
it('gives a framework no workspace at all', function () {
    createFramework()->assertCreated();

    expect(Framework::query()->sole()->getAttributes())->not->toHaveKey('workspace_id');
});

it('derives a globally unique address from the name', function () {
    createFramework()->assertCreated()->assertJsonPath('data.slug', 'board-game-design-framework');
});

it('accepts an address the author typed', function () {
    createFramework(['slug' => 'bgdf'])->assertCreated()->assertJsonPath('data.slug', 'bgdf');
});

/**
 * The two address paths behave differently on purpose. A derived address is a suggestion, so a collision
 * is resolved; a typed one is a URL the author intends to publish, so a collision is reported.
 */
it('suffixes a derived address that collides', function () {
    Framework::factory()->withSlug('board-game-design-framework')->create();

    createFramework()->assertCreated()->assertJsonPath('data.slug', 'board-game-design-framework-2');
});

it('refuses a typed address that is already taken', function () {
    Framework::factory()->withSlug('bgdf')->create();

    /*
     * A 422 with a message rather than a validation error, which is what the JSON surface does with a
     * domain rule: the module's exception renderer turns a violation that names a field into a form
     * error for the screens and into a status plus a message for the API.
     */
    createFramework(['slug' => 'bgdf'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Another framework already uses the address "bgdf".');

    expect(Framework::query()->count())->toBe(1);
});

it('refuses an address reserved by the framework routes', function (string $slug) {
    createFramework(['slug' => $slug])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('slug');
})->with(['versions', 'publish', 'archive']);

it('requires a name long enough to mean something', function (?string $name) {
    createFramework(['name' => $name])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('name');
})->with([null, '', 'a']);

it('ignores a status somebody tried to set', function () {
    createFramework(['status' => FrameworkStatus::Published->value])
        ->assertCreated()
        ->assertJsonPath('data.status', FrameworkStatus::Draft->value);
});

it('announces a new methodology by address as well as by id', function () {
    Event::fake([FrameworkCreated::class]);

    createFramework(['slug' => 'bgdf'])->assertCreated();

    Event::assertDispatched(function (FrameworkCreated $event) {
        return $event->slug === 'bgdf'
            && $event->name === 'Board Game Design Framework'
            && $event->createdBy === $this->author->id;
    });
});

it('publishes a draft framework', function () {
    $framework = Framework::factory()->withSlug('bgdf')->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/publish')
        ->assertOk()
        ->assertJsonPath('data.status', FrameworkStatus::Published->value);
});

it('announces publication', function () {
    Event::fake([FrameworkPublished::class]);

    Framework::factory()->withSlug('bgdf')->create();

    $this->actingAs($this->author)->postJson('/api/v1/frameworks/bgdf/publish')->assertOk();

    Event::assertDispatched(fn (FrameworkPublished $event) => $event->slug === 'bgdf');
});

/**
 * There is no transition back, so the refusal is structural rather than a check somebody could forget.
 * Republishing is refused for the same reason.
 */
it('refuses to publish a framework twice', function () {
    Framework::factory()->withSlug('bgdf')->published()->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/publish')
        ->assertForbidden();
});

it('freezes a published framework\'s own record', function () {
    Framework::factory()->withSlug('bgdf')->published()->create();

    $this->actingAs($this->author)
        ->patchJson('/api/v1/frameworks/bgdf', ['name' => 'Something else'])
        ->assertForbidden();
});

/**
 * A published framework still gains new draft versions, and that combination is the whole mechanism by
 * which a methodology evolves. Without it, publishing would end the framework.
 */
it('still lets a published framework gain a new version', function () {
    Framework::factory()->withSlug('bgdf')->published()->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions')
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);
});

it('archives a framework and stops it gaining versions', function () {
    Framework::factory()->withSlug('bgdf')->published()->create();

    $this->actingAs($this->author)->postJson('/api/v1/frameworks/bgdf/archive')
        ->assertOk()
        ->assertJsonPath('data.status', FrameworkStatus::Archived->value);

    $this->actingAs($this->author)->postJson('/api/v1/frameworks/bgdf/versions')
        ->assertForbidden();
});

it('names the signed in account as the author, whatever the body says', function () {
    createFramework(['created_by' => $this->designer->id])->assertCreated();

    expect(Framework::query()->sole()->created_by)->toBe($this->author->id);
});
