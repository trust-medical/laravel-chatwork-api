<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use Illuminate\Contracts\Container\Container;
use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\Credentials;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthClient;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\OAuthTokenProvider;
use TrustMedical\LaravelChatworkApi\Auth\OAuth\TokenRepository;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkConfigurationException;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

/**
 * Facade `Chatwork::` と DI コンテナが解決する公開エントリポイント。connection 名を
 * {@see Connection} へ解決し、有効な credentials とアクティブなレスポンスモードで
 * {@see ChatworkClient} を組み立てる。
 *
 * {@see ChatworkClient} と異なり readonly ではなく可変。connection / 認証オーバーライド /
 * mode を差し替える with 系メソッドは shallow clone した新インスタンスを返す
 * （{@see self::withMode()} 参照）。
 */
final class ChatworkManager
{
    private ?Connection $connection = null;

    private ResponseMode $mode;

    private ?Credentials $credentialsOverride = null;

    /**
     * @param  ResponseMode  $mode  既定の戻り値モード。`config('chatwork.response.mode')`
     *                              から ServiceProvider 経由で注入される。デフォルト値は
     *                              `app(ChatworkManager::class)` 等の直接解決を壊さないために必須。
     */
    public function __construct(
        private readonly Container $container,
        ResponseMode $mode = ResponseMode::Dto,
    ) {
        $this->mode = $mode;
    }

    /**
     * 指定された（またはデフォルトの）設定済み connection にバインドされたクローンを返す。
     *
     * @throws ChatworkAuthenticationException connection が存在しない、サポートされていない auth ドライバ、またはトークンがない場合。
     */
    public function connection(?string $name = null): self
    {
        $resolved = $name ?? $this->defaultConnectionName();

        $new = clone $this;
        $new->connection = $this->resolveConnection($resolved);

        return $new;
    }

    public function forConnection(Connection $connection): self
    {
        $new = clone $this;
        $new->connection = $connection;

        return $new;
    }

    public function withApiToken(string $token): self
    {
        $new = clone $this;
        $new->credentialsOverride = new ApiTokenCredentials($token);

        return $new;
    }

    public function withBearerToken(string $token): self
    {
        $new = clone $this;
        $new->credentialsOverride = new BearerTokenCredentials($token);

        return $new;
    }

    /**
     * ランタイム指定キー (例: User ID) で TokenRepository を引いて OAuth コネクションをその場で組み立てる。
     *
     * 既存の config 固定 OAuth connection (`auth: 'oauth'`) と同じく {@see OAuthTokenProvider} を経由するため、
     * refresh は `Cache::lock('chatwork:oauth:refresh:<sha256(key)>', 30)` で直列化される。
     * $key は TokenRepository の find()/save() キーになるため、PII を含めない安定 ID
     * (DB の User PK 等) を使うこと。
     *
     * 返される Connection の name は `"oauth:{$key}"` 形式。
     * baseUri / timeout は $base で指定した connection エントリ (省略時は config('chatwork.default'))
     * から継承する。$base 指定が config に存在しない場合は {@see ChatworkAuthenticationException} を throw する。
     *
     * 解決は呼び出し時点で確定する resolve-at-bind セマンティクスのため、戻ってきた manager を
     * 長期保持しないこと (access_token expire 後は使えなくなる)。
     *
     * @throws ChatworkConfigurationException $key が空文字の場合。
     * @throws ChatworkAuthenticationException $base 指定があるが connections.<base> が未設定の場合、
     *                                         TokenRepository に該当 key のトークンが無い場合、refresh 失敗時。
     */
    public function forOAuthKey(string $key, ?string $base = null): self
    {
        if ($key === '') {
            throw new ChatworkConfigurationException(
                'Chatwork::forOAuthKey() requires a non-empty key.',
            );
        }

        $baseName = $base ?? $this->defaultConnectionName();
        $entry = $this->getConnectionEntry($baseName);

        $credentials = $this->buildOAuthTokenProvider($key)->credentials();

        $new = clone $this;
        $new->connection = Connection::make(
            name: 'oauth:' . $key,
            credentials: $credentials,
            baseUri: $this->resolveBaseUri($entry),
            timeout: $this->resolveTimeout($entry),
        );
        $new->credentialsOverride = null;

        return $new;
    }

    public function asArray(): self
    {
        return $this->withMode(ResponseMode::Array);
    }

    public function asDto(): self
    {
        return $this->withMode(ResponseMode::Dto);
    }

    public function asCollection(): self
    {
        return $this->withMode(ResponseMode::Collection);
    }

    public function asResponse(): self
    {
        return $this->withMode(ResponseMode::Response);
    }

    public function asPsrResponse(): self
    {
        return $this->withMode(ResponseMode::PsrResponse);
    }

    public function asResult(): self
    {
        return $this->withMode(ResponseMode::Result);
    }

    /**
     * バインドされた connection を解決し、未設定の場合は設定済みのデフォルトにフォールバックする。
     *
     * @throws ChatworkAuthenticationException デフォルト connection を解決する必要があるが設定が不正な場合。
     */
    public function getConnection(): Connection
    {
        return $this->connection ?? $this->resolveConnection($this->defaultConnectionName());
    }

    /**
     * withApiToken()/withBearerToken() のオーバーライドを適用して connection を解決する。
     *
     * credentialsOverride が設定されている場合は base 接続の static credentials 解決
     * （`buildStaticCredentials` / `buildOAuthCredentials`）をスキップする。これは
     * OAuth 専用プロジェクト等で base connection の token を未設定にしたまま動的トークン
     * （`withBearerToken` / `withApiToken`）を使いたいケースを成立させるため必須。
     * base 接続からは name / base URI / timeout のメタデータのみを引き継ぐ。
     *
     * @throws ChatworkAuthenticationException 基底の connection が未定義の場合、または
     *                                         credentialsOverride 無しで base credentials
     *                                         を解決できない場合。
     */
    public function getEffectiveConnection(): Connection
    {
        if ($this->credentialsOverride === null) {
            return $this->getConnection();
        }

        if ($this->connection !== null) {
            return Connection::make(
                name: $this->connection->name,
                credentials: $this->credentialsOverride,
                baseUri: $this->connection->baseUri,
                timeout: $this->connection->timeout,
            );
        }

        $name = $this->defaultConnectionName();
        $entry = $this->getConnectionEntry($name);

        return Connection::make(
            name: $name,
            credentials: $this->credentialsOverride,
            baseUri: $this->resolveBaseUri($entry),
            timeout: $this->resolveTimeout($entry),
        );
    }

    public function getMode(): ResponseMode
    {
        return $this->mode;
    }

    /**
     * 有効な connection とアクティブなレスポンスモードで ChatworkClient を構築する。
     *
     * @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。
     */
    public function client(): ChatworkClient
    {
        return new ChatworkClient(
            $this->getEffectiveConnection(),
            $this->container->make(ChatworkPendingRequestFactory::class),
            $this->container->make(ResponseMapper::class),
            $this->mode,
        );
    }

    /** @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。 */
    public function rooms(): RoomsResource
    {
        return $this->client()->rooms();
    }

    /** @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。 */
    public function me(): MeResource
    {
        return $this->client()->me();
    }

    /** @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。 */
    public function my(): MyResource
    {
        return $this->client()->my();
    }

    /** @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。 */
    public function contacts(): ContactsResource
    {
        return $this->client()->contacts();
    }

    /** @throws ChatworkAuthenticationException 有効な connection が設定不正な場合。 */
    public function incomingRequests(): IncomingRequestsResource
    {
        return $this->client()->incomingRequests();
    }

    /**
     * 戻り値モード選択の公開エントリポイントは Client 層（{@see ChatworkClient::withMode()}）にある。
     * Manager は非 readonly な可変オブジェクトのため、ここでは shallow clone して mode のみ
     * 差し替える複製イディオムを使い、内部委譲専用として private に閉じる。
     */
    private function withMode(ResponseMode $mode): self
    {
        $new = clone $this;
        $new->mode = $mode;

        return $new;
    }

    /**
     * @throws ChatworkAuthenticationException connection エントリが存在しないか、auth ドライバがサポートされていない場合。
     */
    private function resolveConnection(string $name): Connection
    {
        $entry = $this->getConnectionEntry($name);

        $auth = $entry['auth'] ?? null;
        $credentials = match ($auth) {
            'api_token' => $this->buildStaticCredentials($name, $entry, ApiTokenCredentials::class),
            'bearer' => $this->buildStaticCredentials($name, $entry, BearerTokenCredentials::class),
            'oauth' => $this->buildOAuthCredentials($name, $entry),
            default => throw new ChatworkAuthenticationException(
                sprintf(
                    "Chatwork connection '%s' has unsupported auth driver: %s",
                    $name,
                    is_string($auth) ? $auth : 'null',
                ),
            ),
        };

        return Connection::make(
            name: $name,
            credentials: $credentials,
            baseUri: $this->resolveBaseUri($entry),
            timeout: $this->resolveTimeout($entry),
        );
    }

    /**
     * connections.<name> の設定エントリを取得する（credentials は解決しない）。
     *
     * @return array<string, mixed>
     *
     * @throws ChatworkAuthenticationException エントリが存在しないか配列でない場合。
     */
    private function getConnectionEntry(string $name): array
    {
        $entry = $this->container->make('config')->get(sprintf('chatwork.connections.%s', $name));

        if (! is_array($entry)) {
            throw new ChatworkAuthenticationException(
                sprintf("Chatwork connection '%s' is not configured.", $name),
            );
        }

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolveBaseUri(array $entry): ?string
    {
        $perConnection = $entry['base_uri'] ?? null;
        if (is_string($perConnection) && $perConnection !== '') {
            return $perConnection;
        }

        $global = $this->container->make('config')->get('chatwork.base_uri');

        return is_string($global) ? $global : null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolveTimeout(array $entry): ?int
    {
        $perConnection = $entry['timeout'] ?? null;
        if (is_numeric($perConnection)) {
            return (int) $perConnection;
        }

        $global = $this->container->make('config')->get('chatwork.timeout');

        return is_numeric($global) ? (int) $global : null;
    }

    private function defaultConnectionName(): string
    {
        $name = $this->container->make('config')->get('chatwork.default');

        return is_string($name) && $name !== '' ? $name : 'default';
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  class-string<ApiTokenCredentials|BearerTokenCredentials>  $credentialsClass
     *
     * @throws ChatworkAuthenticationException エントリに有効なトークンが設定されていない場合。
     */
    private function buildStaticCredentials(string $name, array $entry, string $credentialsClass): Credentials
    {
        $token = $entry['token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new ChatworkAuthenticationException(
                sprintf("Chatwork connection '%s' has no token configured.", $name),
            );
        }

        return new $credentialsClass($token);
    }

    /**
     * @param  array<string, mixed>  $entry
     *
     * @throws ChatworkAuthenticationException OAuth TokenRepository にトークンセットが未保存の場合、またはリフレッシュ競合が解決できない場合。
     */
    private function buildOAuthCredentials(string $name, array $entry): Credentials
    {
        $tokenSetKey = $entry['connection_name'] ?? $name;
        if (! is_string($tokenSetKey) || $tokenSetKey === '') {
            $tokenSetKey = $name;
        }

        return $this->buildOAuthTokenProvider($tokenSetKey)->credentials();
    }

    /**
     * @throws ChatworkAuthenticationException refresh が必要だが TokenRepository が空、または lock 競合で解決できない場合。
     */
    private function buildOAuthTokenProvider(string $tokenSetKey): OAuthTokenProvider
    {
        $leeway = $this->container->make('config')->get('chatwork.oauth.refresh_leeway_seconds');

        return new OAuthTokenProvider(
            connectionName: $tokenSetKey,
            repository: $this->container->make(TokenRepository::class),
            oauth: $this->container->make(OAuthClient::class),
            leewaySeconds: is_numeric($leeway) ? (int) $leeway : 60,
        );
    }
}
