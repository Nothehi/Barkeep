<?php

/*
|--------------------------------------------------------------------------
| Forwarded requests
|--------------------------------------------------------------------------
|
| In production Traefik terminates TLS and forwards plain HTTP to Octane, so
| the only record of how a visitor actually reached the site is in the
| `X-Forwarded-*` headers. The root template addresses the bundle absolutely,
| which makes that record load bearing rather than cosmetic: assets addressed
| on the wrong scheme are not fetched at all, so React never mounts and `#app`
| is served empty. These cover both halves — that a forwarded request is read,
| and that one arriving from outside the private network is not.
|
| The addresses below are picked with care. Symfony counts the RFC 5737
| documentation ranges (192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24) as
| private, so the usual example addresses are all trusted and would prove
| nothing here. 8.8.8.8 is genuinely public.
|
*/

test('a request forwarded over TLS addresses its assets over https', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get(route('home'))
        ->assertOk()
        ->assertSee('src="https://', escape: false)
        ->assertDontSee('src="http://', escape: false);
});

/**
 * The counterpart, and the one a blanket `URL::forceScheme('https')` fails:
 * the production stack publishes the container's port on the host, so it is
 * reachable without ever passing through Traefik. A page served there has to
 * address its assets on the scheme that port actually speaks.
 */
test('a request that reaches the container directly addresses its assets over http', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('src="http://', escape: false)
        ->assertDontSee('src="https://', escape: false);
});

/**
 * Only proxies on a private subnet are trusted. Anything else claiming to have
 * forwarded the request is choosing the scheme for us, and could equally
 * choose the client address that rate limiting and the logs go on.
 */
test('a forwarded scheme from outside the private network is ignored', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get(route('home'))
        ->assertOk()
        ->assertDontSee('src="https://', escape: false);
});

/**
 * The other reason to read these headers: without it every request looks like
 * it came from Traefik, and the whole site shares one client's rate limit.
 */
test('the client address behind the proxy is the forwarded one', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.2'])
        ->withHeaders(['X-Forwarded-For' => '8.8.4.4'])
        ->get(route('home'))
        ->assertOk();

    expect(request()->ip())->toBe('8.8.4.4');
});
