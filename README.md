# laravel-chatwork-api

[Chatwork API v2](https://developer.chatwork.com/reference) を Laravel から安全に利用するための Composer パッケージです。**Facade / DI / Laravel Notification** の3経路を公式サポートします。

- PHP `^8.3`
- Laravel `^11.0 || ^12.0 || ^13.0`
- Chatwork API v2（Base URI: `https://api.chatwork.com/v2`）

## 特長

- すべてのエンドポイント（rooms / messages / members / tasks / files / links / contacts / me / my / incoming_requests）をカバー
- API Token と OAuth2 Bearer Token の両認証に対応（排他は型で構造的に保証）
- 戻り値モードを呼び出し側でチェーン切り替え（DTO / 配列 / Collection / Response / Result）
- `readonly` Response DTO と immutable Request オブジェクトによる型安全な API
- Laravel Notification channel（`ChatworkChannel`）を同梱
- OAuth2 認可フロー（認可URL生成・callback・refresh token、`Cache::lock` による多重 refresh 防止）

## インストール

```bash
composer require trust-medical/laravel-chatwork-api
```

ServiceProvider と Facade は Laravel のパッケージ自動検出で登録されるため、手動登録は不要です。

設定ファイルを publish します:

```bash
php artisan vendor:publish --tag="chatwork-config"
```

`.env` に最低限の認証情報を設定します:

```dotenv
CHATWORK_API_TOKEN=your-api-token
```

## 設定

`config/chatwork.php` の主なキー:

| キー | 既定値 | 説明 |
|---|---|---|
| `default` | `default` | 使用する connection 名（`CHATWORK_CONNECTION`） |
| `base_uri` | `https://api.chatwork.com/v2` | API ベース URI |
| `timeout` | `10` | リクエストタイムアウト秒 |
| `response.mode` | `dto` | 既定の戻り値モード（`CHATWORK_RESPONSE_MODE`。無効値は `ChatworkConfigurationException`） |
| `connections` | API Token connection 1件 | 複数 connection 定義可 |
| `oauth` | — | OAuth2 設定（後述） |
| `oauth.timeout` | `10` | OAuth トークン要求のタイムアウト秒（`CHATWORK_OAUTH_TIMEOUT`） |

複数 connection の例:

```php
'connections' => [
    'default' => [
        'auth' => 'api_token',
        'token' => env('CHATWORK_API_TOKEN'),
    ],
    'bot' => [
        'auth' => 'api_token',
        'token' => env('CHATWORK_BOT_TOKEN'),
    ],
],
```

## 基本的な使い方

```php
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

// メッセージ送信
Chatwork::rooms()->messages()->create(123, 'こんにちは');

// 自分の情報を取得（既定は DTO で返る）
$me = Chatwork::me()->get();
echo $me->name;
```

### connection の切り替え

```php
// 設定済み connection を名前で指定
Chatwork::connection('bot')->rooms()->messages()->create(123, 'bot からの通知');

// その場限りのトークンで実行
Chatwork::withApiToken($token)->me()->get();
Chatwork::withBearerToken($oauthAccessToken)->me()->get();
```

`connection()` / `withApiToken()` / `withBearerToken()` / `as*()` は新しい manager を返すイミュータブル設計のため、安全にチェーンできます。`ChatworkManager` はコンテナ singleton ですが、これらは共有インスタンスを mutate せず clone を返すため、**Laravel Octane / Swoole / キューワーカー等の常駐プロセスでもリクエスト間で connection・認証情報・戻り値モードが漏れません**。

## 戻り値モード

既定は `asDto()`。呼び出し側でチェーンして変更できます。

| モード | 成功時 | 4xx / 5xx |
|---|---|---|
| `asDto()` | readonly DTO | `ChatworkRequestException` を throw |
| `asArray()` | 配列 | `ChatworkRequestException` を throw |
| `asCollection()` | `Illuminate\Support\Collection` | `ChatworkRequestException` を throw |
| `asResponse()` | Laravel HTTP Response | throw しない |
| `asPsrResponse()` | PSR-7 Response | throw しない |
| `asResult()` | `Result`（`Http\Result`） | throw しない |

```php
$rooms = Chatwork::asCollection()->rooms()->list();
$raw   = Chatwork::asArray()->me()->get();
$res   = Chatwork::asResponse()->rooms()->find(123);
```

> 送信前バリデーション失敗は戻り値モードに関わらず常に `ChatworkValidationException` を throw します。

### メソッド別の戻り値型（`asDto()` 契約）

各 Resource メソッドのネイティブ署名は `: mixed` です（戻り値型は `ResponseMode`
により実行時に変わるため）。下表は **既定の `asDto()` モード** での戻り値型です。
`asArray()` / `asCollection()` / `asResponse()` / `asPsrResponse()` / `asResult()`
へ切り替えた場合は宣言型・下表の型と実行時型が乖離し、その型解釈は呼び出し側の
責務になります（設計判断の詳細は `docs/03-package-architecture/response-strategy.md`）。

| メソッド | `asDto()` 戻り値型 |
|---|---|
| `rooms()->list()` | `list<RoomData>` |
| `rooms()->create()` | `CreatedRoom` |
| `rooms()->find()` | `RoomData` |
| `rooms()->update()` | `UpdatedRoom` |
| `rooms()->leaveRoom()` / `deleteRoom()` | `NoContentData` |
| `rooms()->messages()->create()` | `CreatedMessage` |
| `rooms()->messages()->list()` | `list<MessageData>` |
| `rooms()->messages()->find()` | `MessageData` |
| `rooms()->messages()->update()` | `UpdatedMessage` |
| `rooms()->messages()->deleteMessage()` | `DeletedMessage` |
| `rooms()->messages()->markAsRead()` | `MarkReadResult` |
| `rooms()->messages()->markAsUnread()` | `MarkUnreadResult` |
| `rooms()->members()->list()` | `list<RoomMemberData>` |
| `rooms()->members()->replaceMembers()` | `ReplacedRoomMembers` |
| `rooms()->tasks()->list()` | `list<RoomTaskData>` |
| `rooms()->tasks()->create()` | `CreatedTask` |
| `rooms()->tasks()->find()` / `updateStatus()` | `RoomTaskData` |
| `rooms()->files()->list()` | `list<RoomFileData>` |
| `rooms()->files()->upload()` | `UploadedRoomFile` |
| `rooms()->files()->find()` | `RoomFileData` |
| `rooms()->links()->find()` / `create()` / `update()` / `deleteLink()` | `RoomLinkData` |
| `contacts()->list()` | `list<ContactData>` |
| `me()->get()` | `MyAccountData` |
| `my()->status()` | `MyStatusData` |
| `my()->tasks()` | `list<MyTaskData>` |
| `incomingRequests()->list()` | `list<IncomingRequestData>` |
| `incomingRequests()->accept()` | `ContactData` |
| `incomingRequests()->decline()` | `NoContentData` |

`list<…>` 系で Chatwork が 204 を返す場合、`asDto()` では `[]` に縮退します
（`contacts()->list()` / `my()->tasks()` / `incomingRequests()->list()`）。

## リソース別の例

### Rooms

```php
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateRoomRequest;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;

Chatwork::rooms()->list();
Chatwork::rooms()->find(123);

Chatwork::rooms()->create(new CreateRoomRequest(
    name: '新規ルーム',
    membersAdminIds: [101, 102],
    description: 'チーム連絡用',
    iconPreset: IconPreset::Meeting,
));

Chatwork::rooms()->update(123, new UpdateRoomRequest(name: '改名後'));

// 破壊的操作は対象を明示した命名（曖昧な delete() は提供しない）
Chatwork::rooms()->leaveRoom(123);   // action_type=leave
Chatwork::rooms()->deleteRoom(123);  // action_type=delete
```

### Messages

```php
Chatwork::rooms()->messages()->create(123, '本文', selfUnread: true);
Chatwork::rooms()->messages()->list(123, force: true);
Chatwork::rooms()->messages()->find(123, '1024');
Chatwork::rooms()->messages()->update(123, '1024', '編集後の本文');
Chatwork::rooms()->messages()->deleteMessage(123, '1024');
Chatwork::rooms()->messages()->markAsRead(123, '1024');
Chatwork::rooms()->messages()->markAsUnread(123, '1024');
```

### Members

```php
use TrustMedical\LaravelChatworkApi\Data\Requests\ReplaceRoomMembersRequest;

Chatwork::rooms()->members()->list(123);

Chatwork::rooms()->members()->replaceMembers(123, new ReplaceRoomMembersRequest(
    membersAdminIds: [101],
    membersMemberIds: [201, 202],
    membersReadonlyIds: [301],
));
```

### Tasks

```php
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomTaskRequest;
use TrustMedical\LaravelChatworkApi\Enums\LimitType;
use TrustMedical\LaravelChatworkApi\Enums\TaskStatus;

Chatwork::rooms()->tasks()->create(123, new CreateRoomTaskRequest(
    body: '見積もりを確認する',
    toIds: [101, 102],
    limit: 1735718400,
    limitType: LimitType::Time,
));

Chatwork::rooms()->tasks()->list(123, status: TaskStatus::Open);
Chatwork::rooms()->tasks()->find(123, 456);
Chatwork::rooms()->tasks()->updateStatus(123, 456, TaskStatus::Done);
```

### Files

```php
use TrustMedical\LaravelChatworkApi\Data\Requests\UploadRoomFileRequest;

Chatwork::rooms()->files()->upload(123, new UploadRoomFileRequest(
    path: storage_path('app/report.pdf'),
    message: '月次レポートです',
));

Chatwork::rooms()->files()->list(123);
Chatwork::rooms()->files()->find(123, 789, createDownloadUrl: true);
```

### Invitation Links

```php
use TrustMedical\LaravelChatworkApi\Data\Requests\RoomLinkRequest;

Chatwork::rooms()->links()->find(123);
Chatwork::rooms()->links()->create(123, new RoomLinkRequest(
    code: 'team-invite',
    needAcceptance: true,
    description: '招待リンク',
));
Chatwork::rooms()->links()->update(123, new RoomLinkRequest(description: '説明更新'));
Chatwork::rooms()->links()->deleteLink(123);
```

### Me / My / Contacts / Incoming Requests

```php
Chatwork::me()->get();
Chatwork::my()->status();
Chatwork::my()->tasks(status: TaskStatus::Open);
Chatwork::contacts()->list();

Chatwork::incomingRequests()->list();
Chatwork::incomingRequests()->accept(456);
Chatwork::incomingRequests()->decline(456);
```

## エラーハンドリング

### 例外

| 例外 | 発生条件 |
|---|---|
| `ChatworkValidationException` | 送信前バリデーション失敗（戻り値モードに関わらず常に throw） |
| `ChatworkRequestException` | 4xx / 5xx（throw 系モード時） |
| `ChatworkAuthenticationException` | 認証情報の解決失敗（connection 不正・OAuth refresh 失敗等） |
| `ChatworkConfigurationException` | 設定・配線が不正（`oauth.state_store` / `oauth.token_repository` に不正クラス、`oauth.route_throttle` 形式不正、`base_uri` スキーム不正など） |

すべての例外は marker interface `ChatworkException` を実装します。本パッケージ由来の例外を一括捕捉したい場合は `catch (ChatworkException $e)` が使えます（`status()` / `violations()` などの固有メソッドは具象例外型で分岐してください）。

```php
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkException;

try {
    Chatwork::rooms()->messages()->create(123, '本文');
} catch (ChatworkException $e) {
    // 本パッケージ由来の全例外をここで捕捉
}
```

`ChatworkRequestException` はエラーボディ2系統を取り出せます:

```php
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

try {
    Chatwork::rooms()->messages()->create(123, '本文');
} catch (ChatworkRequestException $e) {
    $e->status();            // int
    $e->errors();            // string[]（通常 API: {"errors":[...]}）
    $e->error();             // ?string（OAuth: error）
    $e->errorDescription();  // ?string（OAuth: error_description）
    $e->rateLimit();         // ?array（429 時: limit / remaining / reset）
}
```

### `asResult()`（例外を投げない）

```php
$result = Chatwork::asResult()->rooms()->messages()->create(123, '本文');

if ($result->failed()) {
    $result->status();   // int
    $result->errors();   // string[]
    $result->rateLimit();
    return;
}

$data = $result->data();
```

## Notification チャンネル

`Notification` から Chatwork へ送信できます。チャンネルは内部的に `asResult()` 固定で、4xx は permanent failure として例外化、5xx / 429 / ネットワークエラーはそのまま伝播してキュー再試行に委譲されます。

`ChatworkMessage` はメッセージ組み立て専用のビルダー / DTO です（`Notification` ではありません）。`ChatworkNotification` を継承すると `via()` は自動で `ChatworkChannel` に接続されるため、`toChatwork()` を実装するだけで送信できます。

```php
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;

class DeployFinished extends ChatworkNotification
{
    public function toChatwork(object $notifiable): ChatworkMessage
    {
        return (new ChatworkMessage())
            ->toRoom(123)
            ->info('デプロイ完了', "本番反映が完了しました。\nコミット: abc123")
            ->selfUnread();
    }
}
```

`ChatworkNotification` を使わず通常の `Notification` で `via()` に `[ChatworkChannel::class]` を返し、`toChatwork($notifiable): ChatworkMessage` を実装しても構いません（queueable・複数チャンネル併用などはこちら）。

メッセージビルダーは `body()` / `title()` / `code()` / `hr()` / `plain()` / `escape()` / `to()`（TO 付与）/ `toRoom()` / `selfUnread()` を提供します。

送信先は `ChatworkMessage::toRoom()` のほか、notifiable 側の `routeNotificationForChatwork()` でも指定できます（両方指定は競合エラー）:

```php
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

public function routeNotificationForChatwork(): ChatworkRoute
{
    return ChatworkRoute::room(123)->connection('bot');
}
```

## OAuth2

`config/chatwork.php` の `oauth` セクションで設定します。

```dotenv
CHATWORK_OAUTH_CLIENT_ID=...
CHATWORK_OAUTH_CLIENT_SECRET=...
CHATWORK_OAUTH_REDIRECT_URI=https://example.com/chatwork/oauth/callback
```

callback ルートは**既定で無効**です（セキュリティのため）。利用する場合は `config/chatwork.php` で有効化します:

```php
'oauth' => [
    // ...
    'routes_enabled' => true,
    'route_prefix' => 'chatwork/oauth',
    'redirect_after_callback' => '/dashboard',
    'token_repository' => \App\Chatwork\DatabaseTokenRepository::class,
    'state_store' => null, // 既定は Cache ベース
],
```

有効化すると `GET {route_prefix}/callback`（ルート名 `chatwork.oauth.callback`）が登録されます。callback では `state` 検証が必須で、token は設定した `TokenRepository` に保存されます。取得済みトークンは `Chatwork::connection('oauth-connection')` 経由で利用でき、期限切れ時は `Cache::lock` で多重発行を防ぎつつ自動 refresh されます。

> **本番環境の推奨:** 既定の `StateStore` / `TokenRepository` は Cache ストアを使います。`state` の一度きりの消費（リプレイ攻撃防止）には read-and-delete のアトミック性が必要なため、本番では `redis` または `database` キャッシュドライバを使用してください。`array` / `file` ドライバは read→delete が非アトミックで、同一 `state` の二重消費が理論上成立し得ます。永続トークンには独自の `TokenRepository`（例: DB 実装）を設定することを推奨します。

> **トークンの暗号化:** 既定の `CacheTokenRepository` は access/refresh トークンを Laravel の `Encrypter`（`APP_KEY`）で暗号化してからキャッシュへ保存します。Redis / Memcached を直接参照されてもトークンは平文露出しません。`APP_KEY` 未設定だと `MissingAppKeyException` になります（通常の Laravel アプリでは設定済み）。`APP_KEY` をローテーションした場合、暗号化済みの既存トークンは復号できず「未保存」とみなされ、利用者は再認証が必要になります。独自の `TokenRepository` を使う場合は暗号化も自実装の責務です。

### callback ルートの throttle

`config/chatwork.php` の `oauth.route_throttle`（既定 `'10,1'` = 1分あたり10回）が callback ルートに `throttle` ミドルウェアとして適用され、`state` / `code` のブルートフォースを抑制します。`"max,decayMinutes"` 形式の文字列、または名前付き rate limiter 名を指定できます。`null` / 空文字で throttle を無効化します。形式不正値は ServiceProvider 起動時に `ChatworkConfigurationException` になります。

### 独自 RouteServiceProvider からの手動登録

`routes_enabled` を `false` のままにしつつ、任意の middleware / ドメイン / プレフィックス配下で callback ルートを登録したい場合は、`ChatworkServiceProvider::registerOAuthRoutes()` を独自の `RouteServiceProvider` から呼び出せます（意図的に `public`）。

> **`route:cache` 利用時の注意:** OAuth callback ルートは `packageBooted()` 内のクロージャで登録されます。`php artisan route:cache` が有効な環境ではこのクロージャが実行されず、**callback ルートが登録されず**、また **`route_throttle` の形式検証も行われません**。`registerOAuthRoutes()` を独自 `RouteServiceProvider` から手動呼び出しする場合も `route:cache` 下では同様にスキップされます。`route:cache` を使う環境で OAuth callback を利用する場合は、ルートをアプリ側の実 routes ファイルに明示登録し、`route_throttle` の指定が実際に効いているか（`php artisan route:list` 等で）確認してください。

## テスト

```bash
composer test          # Pest 全件
composer test:coverage # カバレッジ（目標 80%+）
composer analyse       # PHPStan
composer lint          # Pint で整形
composer ci            # lint:test + analyse + test
```

すべてのテストは `Http::fake()` で実 API を叩かずに検証しています。

## セキュリティ

- API token / client secret / refresh token をログ・例外メッセージに含めません
- OAuth2 callback は `state` 検証を必須とし、ルートは既定で無効です
- API Token（`x-chatworktoken`）と OAuth2 Bearer（`Authorization: Bearer`）は1リクエストで同時送信されません（`Credentials` 実装で構造的に保証）
- OAuth2 `state` のリプレイ防止には read-and-delete のアトミック性が必要なため、本番では `redis` / `database` キャッシュドライバを推奨します（`array` / `file` は非アトミック）

脆弱性を発見した場合は公開 issue ではなく非公開でご連絡ください。

## ライセンス

[MIT License](LICENSE)
