# パッケージ構造

## Composer

想定パッケージ:

```json
{
  "name": "trust-medical/laravel-chatwork-api",
  "require": {
    "php": "^8.3",
    "illuminate/support": "^11.0 || ^12.0 || ^13.0",
    "illuminate/http": "^11.0 || ^12.0 || ^13.0",
    "illuminate/notifications": "^11.0 || ^12.0 || ^13.0"
  },
  "autoload": {
    "psr-4": {
      "TrustMedical\\LaravelChatworkApi\\": "src/"
    }
  }
}
```

実装時はLaravel package auto-discoveryのため、`extra.laravel.providers` に service provider を登録する。

## src構成案

```text
src/
  ChatworkServiceProvider.php
  ChatworkManager.php
  ChatworkClient.php
  Connection.php
  Facades/
    Chatwork.php
  Auth/
    ApiTokenCredentials.php
    BearerTokenCredentials.php
    Credentials.php
    TokenProvider.php
    OAuth/
      OAuthClient.php
      TokenSet.php
      TokenRepository.php
      StateStore.php
      Controllers/
        OAuthCallbackController.php
  Data/
    Requests/
    Responses/
  Enums/
  Exceptions/
  Http/
    ChatworkPendingRequestFactory.php
    ResponseMapper.php
    Result.php
  Notifications/
    ChatworkChannel.php
    ChatworkNotification.php
    ChatworkMessage.php
    ChatworkRoute.php
  Resources/
    RoomsResource.php
    RoomMessagesResource.php
    RoomMembersResource.php
    RoomTasksResource.php
    RoomFilesResource.php
    RoomLinksResource.php
    ContactsResource.php
    IncomingRequestsResource.php
    MeResource.php
    MyResource.php          # GET /my/status, GET /my/tasks
```

## 設計方針

- `ChatworkManager` は connection解決と client生成を担当する。
- `ChatworkClient` は低レベルHTTP実行とresource factoryを担当する。
- `Resources/*` はエンドポイントごとの公開APIを担当する。
- `Data/Requests/*` は入力検証とpayload構築を担当する。
- `Data/Responses/*` はreadonly DTOを担当する。
- `Notifications/*` はLaravel Notification連携だけを担当する。
- OAuth2は `Auth/OAuth/*` に隔離し、通常のAPIクライアントと責務を混ぜない。

## 依存方向

```text
Facade
  -> ChatworkManager
    -> Connection          # 値オブジェクト（config/DB/動的tokenを統一表現、Connection::make() で生成）
    -> ChatworkClient
      -> Resources
      -> HTTP Client (ChatworkPendingRequestFactory)
      -> ResponseMapper

Notification
  -> ChatworkChannel
    -> ChatworkManager
    -> RoomMessagesResource
```

`Connection` は値オブジェクトであり、`ConnectionFactory` というクラスは導入しない（CLAUDE.md 準拠）。生成は `Connection::make()` ファクトリメソッドのみ。Manager はこの値オブジェクトを内部で解決して `ChatworkClient` を組み立てる。

NotificationはHTTP詳細を直接知らない。
必ず `ChatworkManager` とresourceを通す。

## Interface一覧

実装前に確定させる主要 interface のシグネチャ。

```php
namespace TrustMedical\LaravelChatworkApi\Auth;

interface Credentials
{
    public function applyTo(\Illuminate\Http\Client\PendingRequest $request): \Illuminate\Http\Client\PendingRequest;
}

interface TokenProvider
{
    /**
     * @throws \TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException
     */
    public function credentials(): Credentials;
}

namespace TrustMedical\LaravelChatworkApi\Auth\OAuth;

interface TokenRepository
{
    public function save(TokenSet $tokenSet, array $context = []): void;
    public function find(string $connectionName): ?TokenSet;
}

interface StateStore
{
    public function put(string $state, array $payload, int $ttlSeconds): void;
    public function pull(string $state): ?array;
}
```

`Credentials` の実装は `ApiTokenCredentials` / `BearerTokenCredentials` の2系統のみ。両ヘッダー同時付与を構造的に禁止する。詳細は `docs/04-api-client/authentication.md`。

## 各層の責務

| 層 | 責務 | 担当しないこと |
| --- | --- | --- |
| `ChatworkManager` | connection 解決、戻り値モード状態の保持、Resource factory | HTTP実行 |
| `ChatworkClient` | 低レベル HTTP 実行、Resource インスタンス化、`ChatworkPendingRequestFactory` 経由のリクエスト構築 | 戻り値変換（ResponseMapperに委譲） |
| `ChatworkPendingRequestFactory` | 認証ヘッダー付与、base URI設定、timeout適用、User-Agent付与 | payload整形 |
| `Resources/*` | Request DTO 受け取り、URL組み立て、Client への実行委譲 | 認証ヘッダー直接操作、payload エンコード |
| `Data/Requests/*` | 入力検証、型変換（bool→0/1、array→CSV、enum→value）、payload 構築 | HTTP送信 |
| `Data/Responses/*` | JSON → readonly DTO マッピング | HTTPステータス判定 |
| `ResponseMapper` | 戻り値モードに応じた変換、`ChatworkRequestException` の組み立て | リトライ |

