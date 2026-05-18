<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth\Controllers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\StateStore;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * OAuth2 認可コードリダイレクト URI を処理するシングルアクションコントローラ。
 */
final class OAuthCallbackController
{
    private const MAX_CODE_LENGTH = 1024;

    public function __construct(
        private readonly StateStore $stateStore,
        private readonly OAuthClient $oauthClient,
        private readonly TokenRepository $tokenRepository,
        private readonly Config $config,
    ) {}

    /**
     * プロバイダリダイレクトの処理: state 検証・コード交換・トークン永続化。
     *
     * `state` パラメータは StateStore への単回限りのルックアップで検証する（CSRF 保護）。
     * state が存在しないか不明な場合、トークンエンドポイントへの通信を行わず 400 を返す。
     * 成功時は access token を永続化し、正規化した内部パスにリダイレクトする。
     * コード交換・保存・設定起因の失敗はすべて捕捉し、固定の理由コードのみを
     * 返す（例外メッセージ・code・client_secret をレスポンスへ漏らさない）。
     */
    public function __invoke(Request $request): Response
    {
        // security: プロバイダから渡される error / error_description は信用境界外。
        // 値をそのまま返すとフィッシング・情報漏洩につながるため、固定文字列のみ返す。
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

        $connection = isset($payload['connection']) && is_string($payload['connection'])
            ? $payload['connection']
            : 'default';

        try {
            $tokenSet = $this->oauthClient->exchange($code);
            $this->tokenRepository->save($tokenSet, ['connection' => $connection]);
        } catch (ChatworkRequestException) {
            return $this->errorResponse(502, 'token_exchange_failed');
        } catch (InvalidArgumentException|RuntimeException) {
            return $this->errorResponse(500, 'oauth_misconfigured');
        }

        $redirectConfig = $this->config->get('chatwork.oauth.redirect_after_callback');
        $target = is_string($redirectConfig) ? $this->normalizeRedirect($redirectConfig) : '/';

        return redirect()->to($target);
    }

    /**
     * 設定済みのコールバック後リダイレクト先を安全な内部パスに正規化。
     *
     * 単一の `/` で始まらない値、プロトコル相対 (`//`)、バックスラッシュエスケープ (`/\`)
     * はすべて `/` に縮退し、redirect_after_callback 設定値を介した
     * open-redirect 攻撃を防止する。
     */
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
