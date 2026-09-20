# symfony-guard

Symfony middleware adapter for [guard-core-php](https://github.com/rennf93/guard-core-php): maps Symfony `HttpFoundation` Request objects to the guard-core engine and translates block verdicts back to Symfony-native responses. Works with Symfony 6.4 LTS and 7.x.

## Install

```bash
composer require rennf93/symfony-guard
```

Until `rennf93/guard-core-php` has a Packagist release, point composer at its repository and allow dev stability:

```json
{
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        { "type": "vcs", "url": "https://github.com/rennf93/guard-core-php" }
    ]
}
```

## Usage

Wrap your kernel with the middleware (front controller or wherever the kernel is assembled):

```php
use RenzoFranceschini\GuardCore\Config\SecurityConfig;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCoreSymfony\GuardMiddleware;
use Symfony\Component\HttpFoundation\Request;

// Anywhere your App\Kernel is created
$kernel = new GuardMiddleware(
    new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']),
    new GuardEngine(new SecurityConfig(
        enableRedis: false,
        blacklist: ['192.0.2.0/24'],
        rateLimit: 100,
        rateLimitWindow: 60,
        enableRateLimiting: true,
    ))
);
```

```php
// public/index.php
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
```

Blocked requests get the engine's block verdict translated exactly (status, body, headers) as a `Symfony\Component\HttpFoundation\Response`: `403 Forbidden` for a blacklisted IP, `429 Too many requests` with `Retry-After` for a rate limit hit. Passing requests continue into the wrapped kernel untouched.

## Lifecycle

PHP shared-nothing applies: construct `GuardEngine` (and therefore `GuardMiddleware`) per request in classic FPM, or per worker under long-running runtimes (FrankenPHP, RoadRunner, workerman). The middleware holds no mutable state of its own. In-memory fallbacks are per-request safety nets; distributed rate limits, IP bans, and cloud-range caches require Redis (set `enableRedis: true` and point `REDIS_HOST`/`REDIS_PORT` at your instance).

## Behavior notes

- Fail-closed: if the engine throws, the middleware returns the engine's fail-closed response (`500 Security check failed`, honorably overridden by `customErrorResponses`) instead of letting the request through.
- Main request only: the middleware screens the main kernel request and passes sub-requests straight into the wrapped kernel. Sub-requests are internal and derived from a main request that was already screened; screening them again would double-count rate-limit hits (fragments, forwards).
- Bounded body read: the request body is scanned as a prefix of at most 256 KiB (`SymfonyGuardRequest::MAX_BODY_BYTES`, matching the engine's full-scan window). The framework materializes the full body in memory; the engine only ever sees the capped prefix. Payloads beyond the prefix, or signatures split across its boundary, are not detected.
- Client address: the adapter maps the engine's `clientHost()` onto `$request->getClientIp()`. Without Symfony's trusted-proxies configuration that is the connecting `REMOTE_ADDR`, and the engine's own `trusted_proxies` / `X-Forwarded-For` resolution applies. With Symfony trusted proxies configured, Symfony resolves the forwarded chain first and the engine sees the resolved client. Pick one side to do the resolving; configuring both can double-hop.
- With `redisFailOpen: true` the middleware constructs and serves requests even when Redis is unreachable; with `redisFailOpen: false` construction fails closed.
- No security headers or CORS are added by this adapter. (Symfony response mechanics put a `Date` and a private `Cache-Control` on every response object; the engine's own headers are copied exactly.)

## Testing

```bash
composer lint
composer test
```

`composer test` runs the plain-PHP suite in `bin/test_symfony.php` (unit coverage always; set `REDIS_HOST` to a reachable Redis to include the shared-state integration cases).

## Status

No git tags or releases yet; the package installs from source (`dev-main`). The engine, `rennf93/guard-core-php`, is at `v0.1.0`.

## License

MIT
