<?php

declare(strict_types=1);

namespace RenzoFranceschini\GuardCoreSymfony;

use RenzoFranceschini\GuardCore\Request\GuardResponse;
use Symfony\Component\HttpFoundation\Response;

final class ResponseTranslator
{
    public function translate(GuardResponse $response): Response
    {
        $symfony = new Response($response->body() ?? '', $response->statusCode());
        foreach ($response->headers()->all() as $name => $value) {
            $symfony->headers->set($name, $value);
        }

        return $symfony;
    }
}
