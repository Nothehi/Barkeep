<?php

use Illuminate\Support\Facades\Event;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkVersionCreated;
use Modules\DesignFramework\Domain\Events\FrameworkVersionPublished;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

beforeEach(function () {
    $this->author = User::factory()->create(['email' => 'author@barkeep.test']);
    config()->set('design-framework.administrators', ['author@barkeep.test']);

    $this->framework = Framework::factory()->withSlug('bgdf')->createdBy($this->author)->published()->create();
});

it('numbers the first edition one', function () {
    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions', ['name' => 'First edition'])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1)
        ->assertJsonPath('data.label', 'v1')
        ->assertJsonPath('data.name', 'First edition')
        ->assertJsonPath('data.status', FrameworkStatus::Draft->value);
});

/**
 * Version numbers are allocated by the module and never supplied by a caller. They are the identifier
 * games adopt, so a client allowed to name its own could overwrite the meaning of an edition somebody is
 * following.
 */
it('allocates the number itself, ignoring anything the caller sent', function () {
    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions', ['version_number' => 99])
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);
});

it('counts editions forwards', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions')
        ->assertCreated()
        ->assertJsonPath('data.version_number', 2);
});

it('numbers editions per framework rather than globally', function () {
    $other = Framework::factory()->withSlug('other')->published()->create();
    FrameworkVersion::factory()->nextFor($other)->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions')
        ->assertCreated()
        ->assertJsonPath('data.version_number', 1);
});

/**
 * A new edition starts empty rather than as a copy. Cloning a version is a genuinely useful feature and
 * a different operation, with real decisions in it about content the author wanted to drop — guessing at
 * it here would make "create v2" mean something nobody asked for.
 */
it('opens a new edition empty rather than copying the last one', function () {
    $v1 = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    DesignPhaseDefinition::factory()->inVersion($v1)->count(3)->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions')
        ->assertCreated()
        ->assertJsonPath('data.version_number', 2)
        ->assertJsonPath('data.phases_count', 0);
});

it('announces a new edition by number', function () {
    Event::fake([FrameworkVersionCreated::class]);

    $this->actingAs($this->author)->postJson('/api/v1/frameworks/bgdf/versions')->assertCreated();

    Event::assertDispatched(fn (FrameworkVersionCreated $event) => $event->versionNumber === 1
        && $event->frameworkId === $this->framework->id);
});

it('addresses an edition by its number, scoped to its framework', function () {
    $version = FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->actingAs($this->author)
        ->getJson('/api/v1/frameworks/bgdf/versions/1')
        ->assertOk()
        ->assertJsonPath('data.id', $version->id);
});

it('refuses an edition number belonging to a different framework', function () {
    $other = Framework::factory()->withSlug('other')->published()->create();
    FrameworkVersion::factory()->nextFor($other)->published()->create();

    $this->actingAs($this->author)
        ->getJson('/api/v1/frameworks/bgdf/versions/1')
        ->assertNotFound();
});

it('treats a version segment that is not a plain number as no such version', function (string $segment) {
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->actingAs($this->author)
        ->getJson("/api/v1/frameworks/bgdf/versions/{$segment}")
        ->assertNotFound();
})->with(['v1', '01', '1.0', '0']);

it('edits a draft edition', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->author)
        ->patchJson('/api/v1/frameworks/bgdf/versions/1', ['description' => 'Reworded.'])
        ->assertOk()
        ->assertJsonPath('data.description', 'Reworded.');
});

it('publishes an edition and records when', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions/1/publish')
        ->assertOk()
        ->assertJsonPath('data.status', FrameworkStatus::Published->value)
        ->assertJsonPath('data.is_editable', false)
        ->assertJsonPath('data.is_adoptable', true);

    expect(FrameworkVersion::query()->sole()->published_at)->not->toBeNull();
});

it('announces publication with the phase count', function () {
    Event::fake([FrameworkVersionPublished::class]);

    $version = FrameworkVersion::factory()->nextFor($this->framework)->create();
    DesignPhaseDefinition::factory()->inVersion($version)->count(2)->create();

    $this->actingAs($this->author)->postJson('/api/v1/frameworks/bgdf/versions/1/publish')->assertOk();

    Event::assertDispatched(fn (FrameworkVersionPublished $event) => $event->phaseCount === 2
        && $event->versionNumber === 1);
});

/**
 * Publishing an empty edition is a mistake, and it is the author's mistake rather than the module's to
 * refuse. A rule saying "at least one phase" would be this module deciding what a methodology has to look
 * like; the phase count travels on the event so whatever announces new editions can notice instead.
 */
it('allows an edition with no phases to be published', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions/1/publish')
        ->assertOk();
});

it('refuses to publish an edition twice', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions/1/publish')
        ->assertForbidden();
});

it('archives an edition, including a draft nobody finished', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->create();

    $this->actingAs($this->author)
        ->postJson('/api/v1/frameworks/bgdf/versions/1/archive')
        ->assertOk()
        ->assertJsonPath('data.status', FrameworkStatus::Archived->value)
        ->assertJsonPath('data.is_adoptable', false);
});

it('lists editions oldest first, because a version list is a history', function () {
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();
    FrameworkVersion::factory()->nextFor($this->framework)->published()->create();

    $this->actingAs($this->author)
        ->getJson('/api/v1/frameworks/bgdf/versions')
        ->assertOk()
        ->assertJsonPath('data.0.version_number', 1)
        ->assertJsonPath('data.2.version_number', 3);
});
