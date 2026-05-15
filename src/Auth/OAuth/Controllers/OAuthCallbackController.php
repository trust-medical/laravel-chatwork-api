<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class OAuthCallbackController
{
    public function __invoke(Request $request): Response
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (method=%s)', $request->method()));
    }
}
