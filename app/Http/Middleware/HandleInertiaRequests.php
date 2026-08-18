<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = App::getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $this->locale($locale),

            /**
             * The whole catalogue, sent once and remembered by the client.
             *
             * It is a few hundred phrases and it does not change between
             * pages, so shipping it with every response would be pure waste —
             * `Inertia::once()` sends it on the first visit and the client
             * replays it thereafter. Keying it by locale is what makes
             * switching language work: the key changes, the client no longer
             * recognises it, and the new catalogue comes down with the next
             * response.
             */
            'translations' => Inertia::once(fn (): array => $this->translations($locale))
                ->as("translations:{$locale}"),
        ];
    }

    /**
     * The active language, and what the client needs to offer the others.
     *
     * @return array{current: string, direction: string, supported: list<array{code: string, name: string, native: string, direction: string}>}
     */
    private function locale(string $locale): array
    {
        /** @var array<string, array{name: string, native: string, direction: string}> $supported */
        $supported = config('locales.supported', []);

        return [
            'current' => $locale,
            'direction' => $supported[$locale]['direction'] ?? 'ltr',
            /**
             * Mapping over the keys alongside the values re-indexes the result,
             * which is what turns the config's `['fa' => [...]]` into the list
             * of `{code, name, native, direction}` objects the switcher expects.
             */
            'supported' => array_map(
                fn (array $details, string $code): array => ['code' => $code, ...$details],
                $supported,
                array_keys($supported),
            ),
        ];
    }

    /**
     * The phrase catalogue for the given locale.
     *
     * Read through Laravel's own loader rather than by reaching for the file,
     * so the client is guaranteed the same `lang/<locale>.json` that `__()`
     * resolves against on the server. English is the source text, so it has no
     * catalogue and returns none — the client falls back to the key, which is
     * the English phrase.
     *
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        /** @var array<string, string> $catalogue */
        $catalogue = App::make('translation.loader')->load($locale, '*', '*');

        return $catalogue;
    }
}
