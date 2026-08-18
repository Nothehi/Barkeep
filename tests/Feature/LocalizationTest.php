<?php

use Database\Seeders\MechanicSeeder;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/*
 * The language a request is served in comes from a cookie, which is to say
 * from the client. These cover the two halves of that: that a recognised
 * choice is honoured all the way to the rendered page, and that an
 * unrecognised one cannot reach `App::setLocale()`.
 */

test('a request with no locale cookie is served in the default language', function () {
    $this->get(route('login'))->assertOk();

    expect(App::getLocale())->toBe(config('app.locale'));
});

test('a supported locale cookie sets the application language', function () {
    $this->withUnencryptedCookie('locale', 'fa')->get(route('login'))->assertOk();

    expect(App::getLocale())->toBe('fa');
});

/**
 * The cookie is attacker-controlled and is handed to the translator, which
 * resolves it against a path under `lang/`. Anything outside the supported
 * list has to fall back rather than be trusted.
 */
test('an unsupported locale cookie falls back to the default language', function (string $requested) {
    $this->withUnencryptedCookie('locale', $requested)->get(route('login'))->assertOk();

    expect(App::getLocale())->toBe(config('app.locale'));
})->with([
    'a language with no catalogue' => 'de',
    'a traversal attempt' => '../../../etc',
    'an empty string' => '',
]);

/*
 * `dir` is written by the root template rather than by React, so a
 * right-to-left page is laid out correctly on first paint. These are two tests
 * rather than one because a cookie set with `withUnencryptedCookie` stays set
 * for the rest of the test, so the default case needs a request of its own.
 */

test('the root template writes a right-to-left document for Persian', function () {
    $this->withUnencryptedCookie('locale', 'fa')
        ->get(route('login'))
        ->assertSee('lang="fa"', escape: false)
        ->assertSee('dir="rtl"', escape: false);
});

test('the root template writes a left-to-right document by default', function () {
    $this->get(route('login'))
        ->assertSee('lang="en"', escape: false)
        ->assertSee('dir="ltr"', escape: false);
});

test('the shared props describe the active locale and the ones on offer', function () {
    $this->withUnencryptedCookie('locale', 'fa')
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.current', 'fa')
            ->where('locale.direction', 'rtl')
            ->has('locale.supported', 2)
            ->where('locale.supported.1.code', 'fa')
            ->where('locale.supported.1.native', 'فارسی')
        );
});

/**
 * The catalogue is a once prop, so it arrives on the first visit of a locale
 * and is replayed by the client afterwards. English is the source text and so
 * has no catalogue at all — the client falls back to the key, which is already
 * the English phrase.
 */
test('the Persian catalogue is shared with the page', function () {
    $this->withUnencryptedCookie('locale', 'fa')
        ->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('translations.Log in', 'ورود')
            ->where('translations.Password', 'گذرواژه')
        );
});

test('English is served without a catalogue, because the keys are the English text', function () {
    $this->get(route('login'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('translations', []));
});

/*
 * Domain refusals are worded by the exceptions that raise them and reach the
 * client through the API, so the API has to answer in the reader's language
 * too rather than defaulting everybody to English.
 */
test('the API answers domain refusals in the requested language', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    /**
     * `withCredentials` is what makes the test harness send cookies on a JSON
     * request; a browser does it unprompted for a same-origin XHR, which is the
     * only way this API is ever called.
     */
    $response = $this->actingAs($user)
        ->withCredentials()
        ->withUnencryptedCookie('locale', 'fa')
        ->postJson(route('api.workspaces.members.invitations.store', $workspace->slug), [
            'email' => $user->email,
            'role' => 'member',
        ]);

    $response->assertStatus(409)
        ->assertJsonPath('message', __('This person is already a member of the workspace.', locale: 'fa'));
});

test('validation messages are translated', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->ownedBy($user)->withSlug('studio')->create();

    $this->actingAs($user)
        ->withCredentials()
        ->withUnencryptedCookie('locale', 'fa')
        ->postJson(route('api.workspaces.members.invitations.store', $workspace->slug), [
            'email' => '',
            'role' => 'member',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', trans('validation.required', [
            'attribute' => trans('validation.attributes.email', locale: 'fa'),
        ], 'fa'));
});

/**
 * The design vocabulary is stored in English on purpose — it is the platform's
 * shared list, and the English term is the stable identity behind each slug.
 * What a reader sees is translated on the way out, so a Persian session gets
 * Persian words while two studios still mean the same term.
 */
test('the seeded design vocabulary reaches the client in the reader\'s language', function () {
    $this->seed(MechanicSeeder::class);

    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();
    $this->actingAs($user)->post(route('workspaces.activate', 'studio'));

    $response = $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'fa')
        ->get(route('mechanics.index'));

    /** @var array<int, array<string, mixed>> $mechanics */
    $mechanics = $response->viewData('page')['props']['mechanics']['data'];

    $term = collect($mechanics)->firstWhere('slug', 'worker-placement');

    expect($term['name'])->toBe(__('Worker placement', locale: 'fa'))
        ->and($term['description'])->toBe(__(
            'Players take turns claiming action spaces, and a space taken is a space nobody else can use this round.',
            locale: 'fa',
        ));
});

test('a term a curator adds is shown as they typed it', function () {
    $user = User::factory()->create();
    Workspace::factory()->ownedBy($user)->withSlug('studio')->create();
    $this->actingAs($user)->post(route('workspaces.activate', 'studio'));

    Mechanic::factory()->create([
        'name' => 'Roll and write',
        'description' => null,
    ]);

    $this->actingAs($user)
        ->withUnencryptedCookie('locale', 'fa')
        ->get(route('mechanics.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('mechanics.data.0.name', 'Roll and write')
            ->where('mechanics.data.0.description', null)
        );
});
