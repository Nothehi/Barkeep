<?php

use Inertia\Testing\AssertableInertia;
use Modules\Identity\Domain\Models\User;

/*
|--------------------------------------------------------------------------
| The landing page
|--------------------------------------------------------------------------
|
| The only screen in the application somebody can reach without an account,
| which is what these cover: that it is genuinely open, that signing in does
| not take it away, and that it arrives translated rather than falling back to
| English for a reader who has asked for Persian.
|
*/

test('the landing page is open to a visitor with no account', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->where('auth.user', null)
        );
});

/**
 * The page offers a way into the application, and which way depends on
 * whether there is an account already. That decision is made in the header
 * from the shared `auth` prop, so the prop has to survive a signed-in visit
 * to the public site — the route is deliberately outside the `auth`
 * middleware, and a redirect here would mean nobody could ever read the page
 * again.
 */
test('a signed in visitor still gets the landing page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->where('auth.user.id', $user->id)
        );
});

/**
 * The landing copy is the first thing a Persian reader sees, and an untranslated
 * phrase there is invisible at runtime — they are quietly shown English. The
 * catalogue guard in `tests/Unit/TranslationCatalogueTest.php` proves every
 * phrase has an entry; this proves the entries actually reach this page.
 */
test('the landing page is served right to left in Persian, with its copy translated', function () {
    $response = $this->withUnencryptedCookie('locale', 'fa')->get(route('home'));

    $response->assertOk()
        ->assertSee('lang="fa"', escape: false)
        ->assertSee('dir="rtl"', escape: false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale.direction', 'rtl')
            ->where('translations.Board-game design, made executable', 'طراحی بازی رومیزی، این‌بار اجراشدنی')
            ->where('translations.How it works', 'چطور کار می‌کند')
        );
});

/**
 * The showcase draws one card per supported language, formatted through
 * `Intl` for that locale, so the shared prop has to describe every language
 * on offer rather than only the active one.
 */
test('every supported language is described for the showcase', function () {
    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('locale.supported', count(config('locales.supported')))
            ->where('locale.supported.0.direction', 'ltr')
            ->where('locale.supported.1.direction', 'rtl')
            ->where('locale.supported.1.native', 'فارسی')
        );
});
