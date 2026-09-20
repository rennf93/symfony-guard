<?php

declare(strict_types=1);

namespace RenzoFranceschini\GuardCoreSymfony;

use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCore\Redis\GuardRedisException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final class GuardMiddleware implements HttpKernelInterface, TerminableInterface
{
    private readonly ResponseTranslator $translator;

    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly GuardEngine $engine
    ) {
        $this->translator = new ResponseTranslator();
        try {
            $engine->initialize();
        } catch (GuardRedisException $e) {
            if (!$engine->config()->redisFailOpen) {
                throw $e;
            }
        }
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        if ($type !== HttpKernelInterface::MAIN_REQUEST) {
            return $this->kernel->handle($request, $type, $catch);
        }

        try {
            $blocked = $this->engine->execute(new SymfonyGuardRequest($request));
        } catch (\Throwable) {
            return $this->translator->translate($this->engine->failClosedResponse());
        }

        if ($blocked !== null) {
            return $this->translator->translate($blocked);
        }

        return $this->kernel->handle($request, $type, $catch);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
    }
}
