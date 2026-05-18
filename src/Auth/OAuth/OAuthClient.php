<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

/**
 * OAuth2 認可コードクライアント: 認可 URL の構築、トークンエンドポイントへの
 * コード交換および refresh リクエストの実行。
 */
final class OAuthClient
{
    private const STATE_TTL_SECONDS = 600;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly StateStore $stateStore,
        private readonly array $config,
    ) {}

    /**
     * 新規 CSRF state を生成・永続化し、認可 URL を構築して返す。
     *
     * @param  array<string, mixed>  $context  state ストアを経由してコールバックまで往復させる任意データ。
     * @param  list<string>|null  $scopes  指定時にスペース結合して `scope` クエリパラメータに付与。
     *
     * @throws \RuntimeException 必須の `chatwork.oauth.*` 設定キーが存在しないか空の場合。
     */
    public function buildAuthorizationUrl(
        array $context = [],
        ?array $scopes = null,
        string $connectionName = 'default',
    ): string {
        $state = $this->generateState();

        $this->stateStore->put(
            state: $state,
            payload: ['connection' => $connectionName, 'context' => $context],
            ttlSeconds: self::STATE_TTL_SECONDS,
        );

        $query = [
            'client_id' => $this->configString('client_id'),
            'redirect_uri' => $this->configString('redirect_uri'),
            'response_type' => 'code',
            'state' => $state,
        ];

        if ($scopes !== null) {
            $query['scope'] = implode(' ', $scopes);
        }

        return $this->configString('authorization_url') . '?' . http_build_query($query);
    }

    /**
     * 認可コードをトークンセットに交換。
     *
     * @throws ChatworkRequestException トークンエンドポイントが 4xx/5xx を返した場合。
     * @throws \RuntimeException 必須の設定キーが存在しないか、レスポンスボディが JSON でない場合。
     */
    public function exchange(string $code): TokenSet
    {
        return $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->configString('client_id'),
            'client_secret' => $this->configString('client_secret'),
            'redirect_uri' => $this->configString('redirect_uri'),
        ]);
    }

    /**
     * refresh token から新しいトークンセットを取得。
     *
     * トークンエンドポイントからの失敗レスポンスは、汎用リクエストエラーではなく
     * 認証失敗として正規化する。
     *
     * @throws ChatworkAuthenticationException refresh リクエストがトークンエンドポイントに拒否された場合。
     * @throws \RuntimeException 必須の設定キーが存在しないか、レスポンスボディが JSON でない場合。
     */
    public function refresh(string $refreshToken): TokenSet
    {
        try {
            return $this->postToken([
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->configString('client_id'),
                'client_secret' => $this->configString('client_secret'),
            ]);
        } catch (ChatworkRequestException $e) {
            throw new ChatworkAuthenticationException('OAuth refresh failed.', previous: $e);
        }
    }

    /**
     * @param  array<string, string>  $params
     *
     * @throws ChatworkRequestException トークンエンドポイントが 4xx/5xx を返した場合。
     * @throws \RuntimeException 必須の設定キーが存在しないか、レスポンスボディが JSON オブジェクトでない場合。
     */
    private function postToken(array $params): TokenSet
    {
        $tokenUrl = $this->configString('token_url');

        $response = Http::asForm()
            ->timeout($this->timeoutSeconds())
            ->post($tokenUrl, $params);

        if ($response->failed()) {
            throw ChatworkRequestException::fromResponse(
                $response,
                'POST',
                $tokenUrl,
                'issueOAuthToken',
            );
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new \RuntimeException('OAuth token endpoint returned a non-JSON object body.');
        }

        /** @var array<string, mixed> $body */
        return TokenSet::fromArray($body);
    }

    private function generateState(): string
    {
        return bin2hex(random_bytes(24));
    }

    /**
     * トークンエンドポイント要求のタイムアウト秒数。
     *
     * トークンエンドポイント（別ホスト）の無応答で PHP-FPM / queue worker が
     * 無制限ブロックするのを防ぐ。未設定・不正値は安全側の 10 秒にフォールバック。
     */
    private function timeoutSeconds(): int
    {
        $value = $this->config['timeout'] ?? null;
        $seconds = is_numeric($value) ? (int) $value : 10;

        return $seconds > 0 ? $seconds : 10;
    }

    private function configString(string $key): string
    {
        $value = $this->config[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new \RuntimeException(sprintf('chatwork.oauth.%s is not configured.', $key));
        }

        return $value;
    }
}
