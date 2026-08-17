<?php

use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Enums\MechanicStatus;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;

/*
 * The design vocabulary.
 *
 * The one thing in this module that belongs to nobody. Every game picks from one list, which is the
 * only reason the list is worth having — and it is why curating it is a platform privilege that no
 * amount of standing inside a workspace confers.
 *
 * The asymmetry to keep in view: reading is open to everybody and writing is open to almost nobody.
 * A vocabulary somebody has to be granted sight of is a vocabulary nobody uses.
 */
beforeEach(function () {
    $this->designer = User::factory()->create();
    $this->curator = User::factory()->create(['email' => 'curator@barkeep.test']);

    config()->set('game-design.curators', ['curator@barkeep.test']);
});

it('lets any signed in account read the vocabulary', function () {
    Mechanic::factory()->named('Worker placement')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Worker placement')
        ->assertJsonPath('data.0.slug', 'worker-placement')
        ->assertJsonPath('data.0.is_available', true);
});

it('turns away a caller with no session at all', function () {
    $this->getJson('/api/v1/mechanics')->assertUnauthorized();
});

/**
 * A curator names the term and the platform decides what to call it in a URL. Two curators typing
 * "Worker Placement" and "worker placement" must not produce two rows that mean the same thing.
 */
it('derives a term\'s address from its name', function () {
    $this->actingAs($this->curator)
        ->postJson('/api/v1/mechanics', [
            'name' => 'Push Your Luck',
            'category' => MechanicCategory::Uncertainty->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'push-your-luck');

    expect(Mechanic::query()->sole()->category)->toBe(MechanicCategory::Uncertainty);
});

/**
 * Refusing at the form would tell a curator nothing about which existing term they collided with.
 * Letting the save through and showing them the duplicate in the list does.
 */
it('resolves a collision by suffix rather than refusing', function () {
    Mechanic::factory()->named('Drafting')->create();

    $this->actingAs($this->curator)
        ->postJson('/api/v1/mechanics', [
            'name' => 'Drafting',
            'category' => MechanicCategory::Cards->value,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'drafting-2');
});

it('refuses a name with nothing sluggable in it', function () {
    $this->actingAs($this->curator)
        ->postJson('/api/v1/mechanics', ['name' => '!!', 'category' => MechanicCategory::Cards->value])
        ->assertStatus(422);

    expect(Mechanic::query()->count())->toBe(0);
});

it('refuses a category that is not on the list', function () {
    $this->actingAs($this->curator)
        ->postJson('/api/v1/mechanics', ['name' => 'Something', 'category' => 'vibes'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('category');
});

/**
 * The privilege that makes a shared vocabulary safe. Editing one of these changes what is displayed
 * on every game that claimed it, in every workspace — so it is not a studio owner's to do.
 */
it('refuses to let an ordinary designer write the vocabulary', function () {
    $mechanic = Mechanic::factory()->named('Worker placement')->create();

    $this->actingAs($this->designer)
        ->postJson('/api/v1/mechanics', ['name' => 'Mine', 'category' => MechanicCategory::Cards->value])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->patchJson("/api/v1/mechanics/{$mechanic->slug}", [
            'name' => 'Renamed',
            'category' => MechanicCategory::Cards->value,
        ])
        ->assertForbidden();

    $this->actingAs($this->designer)
        ->postJson("/api/v1/mechanics/{$mechanic->slug}/archive")
        ->assertForbidden();

    expect(Mechanic::query()->sole()->name)->toBe('Worker placement');
});

it('refuses everybody when no curators are configured', function () {
    config()->set('game-design.curators', []);

    $this->actingAs($this->curator)
        ->postJson('/api/v1/mechanics', ['name' => 'Mine', 'category' => MechanicCategory::Cards->value])
        ->assertForbidden();
});

/**
 * A mechanic's address is a convenience rather than a permanent identifier, and nothing in the
 * platform stores one — games point at the row by id, so a rename is invisible to every design
 * record that claimed it.
 */
it('moves a term\'s address when it is renamed', function () {
    $mechanic = Mechanic::factory()->named('Drafting')->create();

    $this->actingAs($this->curator)
        ->patchJson("/api/v1/mechanics/{$mechanic->slug}", [
            'name' => 'Card drafting',
            'category' => MechanicCategory::Cards->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'card-drafting');

    expect($mechanic->fresh()->id)->toBe($mechanic->id);
});

it('does not push a term to a suffix when its name is unchanged', function () {
    $mechanic = Mechanic::factory()->named('Drafting')->create();

    $this->actingAs($this->curator)
        ->patchJson("/api/v1/mechanics/{$mechanic->slug}", [
            'name' => 'Drafting',
            'category' => MechanicCategory::Cards->value,
            'description' => 'A better definition.',
        ])
        ->assertOk()
        ->assertJsonPath('data.slug', 'drafting')
        ->assertJsonPath('data.description', 'A better definition.');
});

it('retires a term', function () {
    $mechanic = Mechanic::factory()->named('Roll and move')->create();

    $this->actingAs($this->curator)
        ->postJson("/api/v1/mechanics/{$mechanic->slug}/archive")
        ->assertOk()
        ->assertJsonPath('data.status', 'archived')
        ->assertJsonPath('data.is_available', false);

    expect($mechanic->fresh()->status)->toBe(MechanicStatus::Archived);
});

it('refuses to retire a term twice', function () {
    $mechanic = Mechanic::factory()->named('Roll and move')->archived()->create();

    $this->actingAs($this->curator)
        ->postJson("/api/v1/mechanics/{$mechanic->slug}/archive")
        ->assertForbidden();
});

/**
 * A designer picking mechanics should not be offered a word the platform has withdrawn. The person
 * who withdrew it needs to see that they did.
 */
it('hides a retired term from designers and shows it to curators', function () {
    Mechanic::factory()->named('Worker placement')->create();
    Mechanic::factory()->named('Roll and move')->archived()->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($this->curator)
        ->getJson('/api/v1/mechanics')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

/**
 * Grouped by category, and within a category alphabetically. The category order is the order a
 * design gets built in rather than the alphabet, which is why it cannot come from the database.
 */
it('reads in category order rather than alphabetically', function () {
    Mechanic::factory()->named('Victory points')->inCategory(MechanicCategory::Scoring)->create();
    Mechanic::factory()->named('Worker placement')->inCategory(MechanicCategory::TurnStructure)->create();
    Mechanic::factory()->named('Action points')->inCategory(MechanicCategory::TurnStructure)->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Action points')
        ->assertJsonPath('data.1.name', 'Worker placement')
        ->assertJsonPath('data.2.name', 'Victory points');
});

it('shows one term by its address', function () {
    Mechanic::factory()->named('Set collection')->create();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics/set-collection')
        ->assertOk()
        ->assertJsonPath('data.name', 'Set collection')
        ->assertJsonPath('data.category_label', 'Turn structure');
});

it('does not choke on an address that names nothing', function () {
    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics/no-such-thing')
        ->assertNotFound();

    $this->actingAs($this->designer)
        ->getJson('/api/v1/mechanics/NOT A SLUG')
        ->assertNotFound();
});

it('renders the vocabulary screen', function () {
    Mechanic::factory()->named('Worker placement')->create();

    $this->actingAs($this->designer)
        ->get(route('mechanics.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('mechanics/index')
                ->has('mechanics.data', 1)
                ->where('can.create', false)
                ->where('curation_configured', true)
                ->has('options.categories', 7),
        );

    $this->actingAs($this->curator)
        ->get(route('mechanics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.create', true));
});

/**
 * An installation with nobody configured shows a read-only vocabulary and says why, which is more
 * useful to whoever is setting Barkeep up than a missing button.
 */
it('tells the screen whether anybody can curate', function () {
    config()->set('game-design.curators', []);

    $this->actingAs($this->designer)
        ->get(route('mechanics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('curation_configured', false));
});
