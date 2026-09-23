<?php

declare(strict_types=1);

use App\Config;
use App\Routes;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCoreSymfony\GuardMiddleware;
use Symfony\Component\HttpFoundation\Request;

require __DIR__ . '/../vendor/autoload.php';

$engine = new GuardEngine(Config::securityConfig());
$guard = new GuardMiddleware(new Routes($engine), $engine);

// The guard runs first; admin routes behind the engine gate drive the ban
// manager (see src/Routes.php).
$request = Request::createFromGlobals();
$response = $guard->handle($request);
$response->send();
$guard->terminate($request, $response);
