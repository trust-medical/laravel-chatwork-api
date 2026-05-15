<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\StateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;

final class OAuthCallbackController
{
    private const MAX_CODE_LENGTH = 1024;

    public function __construct(
        private readonly StateStore $stateStore,
        private readonly OAuthClient $oauthClient,
        private readonly TokenRepository $tokenRepository,
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($request->query('error') !== null) {
            return $this->errorResponse(400, 'oauth_provider_error');
        }

        $state = (string) $request->query('state', '');
        if ($state === '') {
            return $this->errorResponse(400, 'invalid_state');
        }

        $payload = $this->stateStore->pull($state);
        if ($payload === null) {
            return $this->errorResponse(400, 'invalid_state');
        }

        $code = (string) $request->query('code', '');
        if ($code === '' || strlen($code) > self::MAX_CODE_LENGTH) {
            return $this->errorResponse(400, 'missing_code');
        }

        $tokenSet = $this->oauthClient->exchange($code);

        $connection = isset($payload['connection']) && is_string($payload['connection'])
            ? $payload['connection']
            : 'default';

        $this->tokenRepository->save($tokenSet, ['connection' => $connection]);

        $redirectConfig = config('chatwork.oauth.redirect_after_callback');
        $target = is_string($redirectConfig) ? $this->normalizeRedirect($redirectConfig) : '/';

        return redirect()->to($target);
    }

    private function normalizeRedirect(string $value): string
    {
        if (! str_starts_with($value, '/')) {
            return '/';
        }

        if (str_starts_with($value, '//') || str_starts_with($value, '/\\')) {
            return '/';
        }

        return $value;
    }

    private function errorResponse(int $status, string $reason): Response
    {
        return response()->json(['error' => $reason], $status);
    }
}
