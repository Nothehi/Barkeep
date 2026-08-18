<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The languages a visitor may actually choose. `config('app.locale')` says
    | what the application falls back to; this says what it is willing to be
    | asked for, and nothing outside this list is ever honoured — the locale
    | arrives from a cookie, which is to say from the client, and handing an
    | unchecked value to `App::setLocale()` would let a request name any file
    | under `lang/`.
    |
    | `native` is the name of the language written in that language, because a
    | reader who cannot yet read the interface has to be able to find their own
    | row in the switcher. `direction` drives the `dir` attribute on `<html>`
    | and the `rtl:` variants in the stylesheet.
    |
    | Adding a language is this array plus `lang/<code>.json`. The English
    | catalogue is the source text itself — `__('Start designing')` — so `en`
    | has no file and needs none.
    |
    */

    'supported' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'direction' => 'ltr',
        ],
        'fa' => [
            'name' => 'Persian',
            'native' => 'فارسی',
            'direction' => 'rtl',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie name
    |--------------------------------------------------------------------------
    |
    | Where the chosen language is kept. It is deliberately a cookie rather
    | than a column on `users`: the sign-in, registration and password-reset
    | screens all need translating too, and a guest has no row to read a
    | preference from. It is left unencrypted in `bootstrap/app.php` for the
    | same reason `appearance` is — the root Blade template has to know the
    | direction before the session is decrypted, and a language choice is not
    | a secret.
    |
    */

    'cookie' => 'locale',

];
