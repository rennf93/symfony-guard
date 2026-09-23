<?php

declare(strict_types=1);

namespace App;

use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The guarded kernel. Admin routes drive the engine's ban manager directly;
 * they are gated by the custom_request admin gate in Config.
 */
final readonly class Routes implements \Symfony\Component\HttpKernel\HttpKernelInterface
{
    public function __construct(private GuardEngine $engine)
    {
    }

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        return match ($request->getPathInfo()) {
            '/', '' => new Response('ok'),
            '/health' => new Response('healthy'),
            '/search' => new Response(
                'search: ' . htmlspecialchars((string) ($request->query->get('q', '')), ENT_QUOTES)
            ),
            '/rate/burst' => new Response('burst ok'),
            '/admin/ban' => $this->ban($request),
            '/admin/unban' => $this->unban($request),
            '/admin/check' => $this->check($request),
            default => new Response('not found', 404),
        };
    }

    private function ban(Request $request): Response
    {
        $ip = (string) $request->query->get('ip', '');
        if ($ip === '') {
            return new JsonResponse(['error' => 'ip query parameter is required'], 400);
        }
        $duration = (int) ($request->query->get('duration') ?? 600);
        $banned = $this->engine->banManager()->ban($ip, $duration, 'manual');

        return new JsonResponse(['ip' => $ip, 'duration' => $duration, 'banned' => $banned]);
    }

    private function unban(Request $request): Response
    {
        $ip = (string) $request->query->get('ip', '');
        if ($ip === '') {
            return new JsonResponse(['error' => 'ip query parameter is required'], 400);
        }
        $this->engine->banManager()->unban($ip);

        return new JsonResponse(['ip' => $ip, 'banned' => false]);
    }

    private function check(Request $request): Response
    {
        $ip = (string) $request->query->get('ip', '');
        if ($ip === '') {
            return new JsonResponse(['error' => 'ip query parameter is required'], 400);
        }

        return new JsonResponse(['ip' => $ip, 'banned' => $this->engine->banManager()->isIpBanned($ip)]);
    }
}
