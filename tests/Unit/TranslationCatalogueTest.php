<?php

use Database\Seeders\MechanicSeeder;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| The catalogue guard
|--------------------------------------------------------------------------
|
| One catalogue serves both sides of the application: `__('Start designing')`
| in a controller and `t('Start designing')` in a component resolve through the
| same entry in `lang/fa.json`, because the key is the English source text.
|
| That only stays true if somebody notices when it stops being true, and a
| missing translation is invisible at runtime — the reader is quietly shown
| English instead. These tests are what makes it visible: adding a phrase
| without translating it fails here rather than shipping.
|
*/

/**
 * Every phrase the source asks for, gathered the way Laravel's own `lang:*`
 * tooling would: `__()` in PHP, `t()` and `choice()` in TypeScript.
 *
 * Deliberately a scan of the source rather than a fixture. A fixture would be
 * a second list to keep in step, and the whole point of keying by English text
 * is that there is only ever one list.
 *
 * @return array<string, list<string>> phrase => the files that ask for it
 */
function translatablePhrases(): array
{
    /**
     * A quoted literal in either language: single- or double-quoted, honouring
     * backslash escapes so an apostrophe inside a phrase does not end it.
     */
    $single = "'((?:[^'\\\\]|\\\\.)*)'";
    $double = '"((?:[^"\\\\]|\\\\.)*)"';

    $patterns = [
        'php' => ['/__\(\s*(?:'.$single.'|'.$double.')/'],
        'ts' => [
            '/\bt\(\s*(?:'.$single.'|'.$double.')/',
            '/\bchoice\(\s*(?:'.$single.'|'.$double.')/',
        ],
    ];

    $sources = [
        'php' => [base_path('app'), base_path('modules'), base_path('database'), base_path('routes')],
        'ts' => [resource_path('js')],
    ];

    $extensions = ['php' => ['php'], 'ts' => ['ts', 'tsx']];

    /**
     * Wayfinder writes these from the route table on every build. They hold
     * URLs, never prose.
     */
    $generated = ['/actions/', '/routes/', '/wayfinder/'];

    $found = [];

    foreach ($sources as $language => $roots) {
        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), $extensions[$language], true)) {
                    continue;
                }

                $path = str_replace(DIRECTORY_SEPARATOR, '/', $file->getPathname());

                if (Str::contains($path, $generated)) {
                    continue;
                }

                $contents = (string) file_get_contents($path);

                foreach ($patterns[$language] as $pattern) {
                    preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER);

                    foreach ($matches as $match) {
                        $literal = ($match[1] ?? '') !== '' ? $match[1] : ($match[2] ?? '');

                        if ($literal === '') {
                            continue;
                        }

                        $phrase = str_replace(["\\'", '\\\\'], ["'", '\\'], $literal);

                        $found[$phrase][] = Str::after($path, base_path().'/');
                    }
                }
            }
        }
    }

    /**
     * The design vocabulary is stored in English and translated by
     * `MechanicResource` through `__($mechanic->name)`. The phrase never
     * appears inside a literal `__()` call, so the scan above cannot see it —
     * it is read from the seeder that writes it, which keeps one list rather
     * than a copy in this file.
     */
    foreach (MechanicSeeder::mechanics() as [, $name, $definition]) {
        $found[$name][] = 'database/seeders/MechanicSeeder.php';
        $found[$definition][] = 'database/seeders/MechanicSeeder.php';
    }

    return $found;
}

/**
 * The phrases that resolve from a PHP file under `lang/<locale>/` rather than
 * from the JSON catalogue. Laravel tells them apart by the dot.
 */
function isNamespacedKey(string $phrase): bool
{
    return Str::contains($phrase, '.') && ! Str::contains($phrase, ' ');
}

test('every phrase the application uses has a Persian translation', function () {
    /** @var array<string, string> $catalogue */
    $catalogue = json_decode((string) file_get_contents(base_path('lang/fa.json')), true);

    $untranslated = [];

    foreach (translatablePhrases() as $phrase => $files) {
        if (isNamespacedKey($phrase)) {
            continue;
        }

        if (! array_key_exists($phrase, $catalogue) || trim($catalogue[$phrase]) === '') {
            $untranslated[$phrase] = array_values(array_unique($files))[0];
        }
    }

    expect($untranslated)->toBe([], 'Add these to lang/fa.json: '.json_encode(
        $untranslated,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ));
});

test('the Persian catalogue has no entries the application no longer uses', function () {
    /** @var array<string, string> $catalogue */
    $catalogue = json_decode((string) file_get_contents(base_path('lang/fa.json')), true);

    $used = translatablePhrases();

    $orphans = array_values(array_diff(array_keys($catalogue), array_keys($used)));

    expect($orphans)->toBe([], 'Remove these from lang/fa.json: '.json_encode(
        $orphans,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
});

/**
 * A dropped `:placeholder` is the failure mode a reviewer is least likely to
 * catch: the sentence still reads, it just silently loses the workspace's name
 * or the number it was counting. An added one is worse — it renders as a
 * literal `:count` on the page.
 */
test('every translation keeps the placeholders of the phrase it translates', function () {
    /** @var array<string, string> $catalogue */
    $catalogue = json_decode((string) file_get_contents(base_path('lang/fa.json')), true);

    $mismatched = [];

    foreach ($catalogue as $phrase => $translation) {
        preg_match_all('/:([A-Za-z][A-Za-z0-9_]*)/', $phrase, $source);
        preg_match_all('/:([A-Za-z][A-Za-z0-9_]*)/', $translation, $target);

        $expected = array_unique($source[1]);
        $actual = array_unique($target[1]);

        sort($expected);
        sort($actual);

        if ($expected !== $actual) {
            $mismatched[$phrase] = [
                'expected' => $expected,
                'found' => $actual,
                'translation' => $translation,
            ];
        }
    }

    expect($mismatched)->toBe([], 'Placeholders differ: '.json_encode(
        $mismatched,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ));
});

/**
 * Every supported locale needs the three things the client reads off it, and
 * `direction` in particular has to be one of the two values the `dir` attribute
 * and the `rtl:` stylesheet variants understand.
 */
test('every supported locale is fully described', function () {
    /** @var array<string, array<string, string>> $supported */
    $supported = config('locales.supported');

    expect($supported)->not->toBeEmpty()
        ->and($supported)->toHaveKey(config('app.locale'));

    foreach ($supported as $code => $details) {
        expect($details)->toHaveKeys(['name', 'native', 'direction'])
            ->and($details['direction'])->toBeIn(['ltr', 'rtl'])
            ->and($details['native'])->not->toBeEmpty();

        /**
         * English is the source text, so it has no catalogue and needs none.
         * Every other locale does.
         */
        if ($code !== config('app.fallback_locale')) {
            expect(base_path("lang/{$code}.json"))->toBeFile();
        }
    }
});

/**
 * The framework's own messages — validation, auth, password resets — resolve
 * from `lang/fa/*.php` rather than from the JSON catalogue. A key the framework
 * has and Persian does not means one English sentence in the middle of a
 * translated form.
 */
test('the Persian validation file covers every rule the framework can report', function () {
    $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
        $flat = [];

        foreach ($lines as $key => $value) {
            $flat = array_merge($flat, is_array($value)
                ? $flatten($value, $prefix.$key.'.')
                : [$prefix.$key => $value]);
        }

        return $flat;
    };

    $english = $flatten(require base_path(
        'vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php'
    ));

    $persian = $flatten(require base_path('lang/fa/validation.php'));

    /**
     * The framework ships a worked example under `custom`; it is documentation
     * rather than a message, and the Persian file leaves `custom` empty.
     */
    unset($english['custom.attribute-name.rule-name']);

    $missing = array_values(array_diff(array_keys($english), array_keys($persian)));

    expect($missing)->toBe([]);
});

test('the Persian auth and password files cover the framework messages', function (string $file) {
    $english = require base_path(
        "vendor/laravel/framework/src/Illuminate/Translation/lang/en/{$file}.php"
    );

    $persian = require base_path("lang/fa/{$file}.php");

    expect(array_diff(array_keys($english), array_keys($persian)))->toBe([]);
})->with(['auth', 'passwords', 'pagination']);
