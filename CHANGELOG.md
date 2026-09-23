Release Notes
=============

___

v1.0.0 (2026-09-24)
-------------------

First stable release (v1.0.0)
-----------------------------

### Added

- **Symfony middleware adapter for guard-core-php 4.0.4.** `GuardMiddleware` maps Symfony `HttpFoundation` Request objects to the guard-core engine (`RenzoFranceschini\GuardCore\Engine\GuardEngine`) and translates block verdicts back to Symfony-native responses, for Symfony 6.4 LTS and 7.x. It wraps the kernel at the front controller or wherever the kernel is assembled.
- **Exact block translation.** Blocked requests get the engine's verdict translated status-for-status, body-for-body and header-for-header into a Symfony response; passing requests continue into the kernel untouched.
- **Fail-closed behavior.** If the engine throws, the request is answered with the engine's fail-closed response instead of being let through.
- **Distributed state via Redis.** Distributed rate limits, IP bans, and cloud-range caches require Redis (`enableRedis: true`); `redisFailOpen: true` keeps serving when Redis is unreachable, `false` fails closed.

### Changed

- **The engine dependency is pinned to `rennf93/guard-core-php` `^4.0.4`**, the first stable engine release, replacing the `^0.1.0` pin to the burned pre-release snapshot tag.

### Internal (v1.0.0)

- Unit and Redis-backed integration suites for the bridging layer in `bin/test_symfony.php` (Redis-backed shared-state cases run when `REDIS_HOST` points at a reachable Redis), plus a `php -l` sweep and `composer audit` in CI across PHP 8.2, 8.3 and 8.4.
- Community workflows, a MkDocs documentation site, and example apps landed via the parity-polish pass.

___
