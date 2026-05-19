<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

use Illuminate\Support\Facades\Cache;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\Credentials;
use TrustMedical\LaravelChatworkApi\Auth\TokenProvider;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;

/**
 * 保存済み OAuth2 トークンセットから Bearer credentials を返す TokenProvider。
 * 有効期限切れ時にはプロセス間協調を伴うリフレッシュを透過的に実行する。
 */
final class OAuthTokenProvider implements TokenProvider
{
    private const LOCK_KEY_PREFIX = 'chatwork:oauth:refresh:';

    /**
     * リフレッシュロックの保持上限。トークンエンドポイント応答が遅延しても
     * ロック保持中に失効しないよう、OAuth `timeout`（既定 10 秒）の数倍を確保する。
     * `chatwork.oauth.timeout` は必ずこの値より十分小さく設定すること。
     * 同値以上だと二重 refresh により invalid_grant でリフレッシュトークンが失効しうる。
     */
    private const LOCK_TTL_SECONDS = 30;

    private const RETRY_DELAY_MICROSECONDS = 500_000;

    public function __construct(
        private readonly string $connectionName,
        private readonly TokenRepository $repository,
        private readonly OAuthClient $oauth,
        private readonly int $leewaySeconds = 60,
    ) {}

    /**
     * Bearer credentials を解決し、leeway ウィンドウ内であればトークンセットをリフレッシュする。
     *
     * @throws ChatworkAuthenticationException connection に対してトークンセットが未保存の場合、またはリフレッシュが必要だが完了できない場合。
     */
    public function credentials(): Credentials
    {
        $tokenSet = $this->repository->find($this->connectionName);
        if ($tokenSet === null) {
            throw new ChatworkAuthenticationException(
                sprintf('No OAuth TokenSet stored for connection "%s".', $this->connectionName),
            );
        }

        if (! $tokenSet->isExpired($this->leewaySeconds)) {
            return new BearerTokenCredentials($tokenSet->accessToken);
        }

        return new BearerTokenCredentials($this->coalescedRefresh($tokenSet)->accessToken);
    }

    /**
     * Cache::lock 下でトークンセットをリフレッシュし、並行ワーカーが最大1回しか refresh を発行しないよう制御する。
     *
     * ロック取得者はプロバイダ呼び出し前に、既にリフレッシュ済みのトークンがないか再確認する。
     * ロック取得に失敗したワーカーは短時間待機してロック保持者が永続化した値を再利用する。
     * それでもまだ有効期限切れの場合は、競合する refresh を発行せず失敗とする。
     *
     * @throws ChatworkAuthenticationException ロックを取得できず、かつ保存済みトークンがまだ有効期限切れの場合。
     */
    private function coalescedRefresh(TokenSet $current): TokenSet
    {
        $lock = Cache::lock($this->lockKey(), self::LOCK_TTL_SECONDS);

        if (! $lock->get()) {
            usleep(self::RETRY_DELAY_MICROSECONDS);
            $latest = $this->repository->find($this->connectionName);
            if ($latest === null || $latest->isExpired($this->leewaySeconds)) {
                throw new ChatworkAuthenticationException(
                    sprintf('OAuth refresh contention for connection "%s" and stored token is still expired.', $this->connectionName),
                );
            }

            return $latest;
        }

        try {
            $latest = $this->repository->find($this->connectionName);
            if ($latest !== null && ! $latest->isExpired($this->leewaySeconds)) {
                return $latest;
            }

            $newTokenSet = $this->oauth->refresh($current->refreshToken);
            $this->repository->save($newTokenSet, ['connection' => $this->connectionName]);

            return $newTokenSet;
        } finally {
            $lock->release();
        }
    }

    private function lockKey(): string
    {
        return self::LOCK_KEY_PREFIX . hash('sha256', $this->connectionName);
    }
}
