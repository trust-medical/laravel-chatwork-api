# Service Container設計

## 登録する主なbinding

```php
ChatworkManager::class
ChatworkClient::class
ChatworkPendingRequestFactory::class
ResponseMapper::class
OAuthClient::class
TokenRepository::class
StateStore::class
```

## Facade

Facade accessorは `chatwork` とする。

```php
Chatwork::connection('sales');
Chatwork::withApiToken($token);
Chatwork::withBearerToken($token);
Chatwork::forConnection($connection);
```

### connection解決の境界

4つのメソッドは用途が異なる。

| メソッド | 入力 | 用途 |
| --- | --- | --- |
| `connection(string $name)` | `config('chatwork.connections.{name}')` から解決 | 静的な複数ワークスペース |
| `forConnection(Connection $connection)` | `Connection` 値オブジェクトを直接 | DB / runtime で組み立てた接続 |
| `withApiToken(string $token)` | API token文字列のみ | 単発の token 上書き（API Token認証） |
| `withBearerToken(string $token)` | Bearer token文字列のみ | 単発の token 上書き（OAuth2 Bearer認証） |

### Manager状態と immutability

これらのメソッドは Manager 自身を変更せず、戻り値モード（`asArray` 等）と同様に **immutable clone** を返す。

```php
$sales = Chatwork::connection('sales');   // clone
$sales->rooms()->messages()->create(...); // sales 接続のみ

Chatwork::rooms()->...; // default 接続に戻る
```

同一チェーン内で複数指定された場合の優先順位は、**呼出順で最後に指定されたもの**を採用する。

```php
Chatwork::connection('sales')->withApiToken($t)->rooms()...
// → withApiToken の $t が有効、sales connection の token は使われない（base_uri 等の他属性は sales のまま）
```

`withApiToken / withBearerToken` は `Credentials` のみを上書きする。`base_uri` / `timeout` は直前の `connection()` / `forConnection()` または default を引き継ぐ。

## config

`config/chatwork.php` をpublish可能にする。

```php
return [
    'default' => env('CHATWORK_CONNECTION', 'default'),

    'base_uri' => env('CHATWORK_BASE_URI', 'https://api.chatwork.com/v2'),

    'timeout' => env('CHATWORK_TIMEOUT', 10),

    'response' => [
        'mode' => 'dto',
    ],

    'connections' => [
        'default' => [
            'auth' => 'api_token',
            'token' => env('CHATWORK_API_TOKEN'),
        ],

        'sales' => [
            'auth' => 'bearer',
            'token' => env('CHATWORK_SALES_BEARER_TOKEN'),
        ],
    ],

    'oauth' => [
        'client_id' => env('CHATWORK_OAUTH_CLIENT_ID'),
        'client_secret' => env('CHATWORK_OAUTH_CLIENT_SECRET'),
        'redirect_uri' => env('CHATWORK_OAUTH_REDIRECT_URI'),
        'authorization_url' => 'https://www.chatwork.com/packages/oauth2/login.php',
        'token_url' => 'https://oauth.chatwork.com/token',
        'routes_enabled' => false,
        'route_prefix' => 'chatwork/oauth',
        'token_repository' => null,
        'state_store' => null,
    ],
];
```

## Connection

`Connection` はconfig由来、DB由来、リクエスト時指定のすべてを表現する値オブジェクトにする。

```php
$connection = Connection::make(
    name: 'tenant-123',
    credentials: Credentials::bearerToken($token),
    baseUri: 'https://api.chatwork.com/v2',
);
```

`Connection` は `TokenProvider` も受け取れる。

```php
$connection = Connection::make(
    name: 'tenant-123',
    credentials: Credentials::fromProvider($provider),
);
```

これにより、アプリケーション側でDBから取得したトークンや、refresh済みtokenを柔軟に利用できる。

## Optional Routes

OAuth2 callback routeはデフォルト無効にする。
有効化された場合のみ service provider がrouteを登録する。

理由:

- OSS利用時に意図しないrouteを追加しない。
- アプリケーション側でmiddlewareや保存処理を制御しやすくする。

