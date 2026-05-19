# 認証設計

## 対応方式

- API Token
- OAuth2 Bearer Token

Chatwork公式のエンドポイント説明では、API Tokenは `x-chatworktoken` headerで送信する。
ローカルOpenAPI JSONにはOAuth2 authorization code flowが含まれている。

## API Token

```php
Chatwork::withApiToken($token)
    ->rooms()
    ->messages()
    ->create($roomId, '本文');
```

HTTP header:

```text
x-chatworktoken: {token}
```

## OAuth2 Bearer Token

```php
Chatwork::withBearerToken($token)
    ->rooms()
    ->messages()
    ->create($roomId, '本文');
```

HTTP header:

```text
Authorization: Bearer {token}
```

## 認証ヘッダー排他保証

1リクエストで `x-chatworktoken` と `Authorization: Bearer` を同時に送らない。排他は `Credentials` 抽象で保証する。

```php
interface Credentials
{
    public function applyTo(PendingRequest $request): PendingRequest;
}
```

- `ApiTokenCredentials::applyTo()` は `withHeaders(['x-chatworktoken' => $token])` のみを設定する。
- `BearerTokenCredentials::applyTo()` は `withToken($token)`（= `Authorization: Bearer ...`）のみを設定する。
- `Credentials` は1つの `Connection` に1つだけ紐づき、両者の and 合成は禁止する。

`Connection::make()` に渡せる `Credentials` のファクトリは2系統に限定する。

```php
Credentials::apiToken($token);
Credentials::bearerToken($token);
Credentials::fromProvider($provider); // TokenProvider が credentials() を返す。中で apiToken/bearerToken のどちらかを返す
```

`ChatworkPendingRequestFactory` は `Connection` から `Credentials` を取得し `applyTo()` を呼ぶ。Factory レベルで両ヘッダー同時付与を起こさない。

## Connection

複数ワークスペースと動的token解決のため、`Connection` を中心にする。

```php
Connection::make(
    name: 'tenant-123',
    credentials: Credentials::apiToken($token),
);
```

```php
Connection::make(
    name: 'tenant-123',
    credentials: Credentials::fromProvider($tokenProvider),
);
```

## TokenProvider

DB、KMS、外部secret manager、refresh済みtokenなどをアプリケーション側で柔軟に扱うため、interfaceを用意する。

```php
interface TokenProvider
{
    /**
     * @throws \TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException
     */
    public function credentials(): Credentials;
}
```

責務:

- 呼ばれるたびに「現時点で有効な」 `Credentials` を返す責務を持つ。
- expired 判定と refresh の発火は実装側の責任。Phase 4 の OAuth refresh では `OAuthTokenProvider` が `TokenRepository` から `TokenSet` を取得し、`expiresAt < now()` なら `OAuthClient::refresh()` を呼んで保存し直してから `Credentials::bearerToken()` を返す。
- 解決に失敗した場合は `ChatworkAuthenticationException` を投げる（HTTP は発生していない）。

実装例:

- `ConfigTokenProvider` — `config/chatwork.php` の値を読む
- `ClosureTokenProvider` — `fn () => Credentials::bearerToken($t)` を受ける
- `DatabaseTokenProvider` — 利用者側で実装、DBから読む
- `OAuthTokenProvider` — `TokenRepository` + `OAuthClient::refresh()` を組み合わせる

### Refresh戦略

OAuth2 で access token を refresh するタイミングは2系統用意する。

1. **事前 refresh**: `TokenProvider::credentials()` 呼出時に `expiresAt - leeway < now()` で判定。leeway は 60 秒（config で変更可）。
2. **事後 refresh**: HTTP 401 を受けたとき、`OAuthClient::refresh()` を1回だけ試行し、Resource methodを再実行する（Phase 4 の確認事項として MVP では事前 refresh のみ実装、事後 refresh は後続フェーズ）。

同時 refresh による token 多重発行を避けるため、`Cache::lock("chatwork:oauth:refresh:{connection}", 10)` を取得してから refresh を実行する。lock 取得失敗時は 1 秒待って再取得し、それでも失敗なら直前に保存された `TokenSet` を読み直す。

## OAuth2機能

パッケージは次を提供する。

- authorization URL生成
- callback state生成と検証
- authorization codeからtoken取得
- refresh tokenによるaccess token更新
- token保存先を差し替える `TokenRepository`
- state保存先を差し替える `StateStore`

> **`StateStore` のアトミック性に関する注意:** デフォルト実装 `CacheStateStore::pull()` は `Cache::pull()`（read-and-delete）で `state` を一度だけ消費する。このアトミック性はキャッシュドライバ依存である。`redis` は `GETDEL` 相当でアトミックだが、`file` / `array` / `database` ドライバは read→delete が非アトミックで、極小の競合窓で同一 `state` の二重消費（リプレイ）が理論上成立し得る。本番環境では `redis`（または同等のアトミックドライバ）を使用すること。独自 `StateStore` 実装に差し替える場合も「各 state は最大1回のみ受理」を保証すること。

## OAuth2 callback

callback routeは任意登録にする。
デフォルトでは無効。

```php
'oauth' => [
    'routes_enabled' => false,
]
```

有効化した場合の想定:

```text
GET /chatwork/oauth/callback
  Query: code, state, [error, error_description]
```

callback controllerは `TokenRepository` に保存処理を委譲する。
保存先が未設定の場合は例外にする。

### ハンドリング分岐

| 入力 | 挙動 |
| --- | --- |
| `code` + 既知の `state` | `OAuthClient::exchange($code)` → `TokenRepository::save()` → 利用者設定の redirect 先へ 302 |
| `state` 不一致 / 期限切れ | 400 を返し、token endpoint には送信しない |
| `error` パラメータあり | 400 を返し、`error_description` をログに残す（client_secret / refresh_token は出さない） |
| `TokenRepository` 未設定 | 500 を返し、構成エラーとして例外を投げる |

レンダリングは利用者がカスタマイズできるよう、`OAuthCallbackController` を継承可能にする。redirect 先は config の `oauth.redirect_after_callback`（未設定なら `/`）から取る。

> **CSRF と `web` ミドルウェアグループについて:** callback は `web` グループ配下に登録されるが、**`GET` ルート**であるため Laravel の `VerifyCsrfToken` は検証を行わない（CSRF トークン検証は `POST`/`PUT`/`PATCH`/`DELETE` のみ）。本エンドポイントの CSRF 防御は `web` グループそのものではなく、**単回使用 `state`**（`OAuthCallbackController` が `StateStore::pull()` で検証し、不正・消費済みは token endpoint に到達せず 400）が担う。したがって利用者側で本ルートに対する独自の CSRF except 設定や `web` グループからの除外を行う必要はない。

> **`route_throttle` と `route:cache` の注意:** callback ルートには `oauth.route_throttle`（既定 `'10,1'`、`null`/空で無効）が `throttle` ミドルウェアとして付与され、`state`/`code` ブルートフォースを抑制する。形式不正値は ServiceProvider 起動時に `ChatworkConfigurationException`。ただしルート登録は `packageBooted()` のクロージャで行われるため、**`php artisan route:cache` 有効時はクロージャが実行されず callback ルートは登録されず、`route_throttle` の形式検証もスキップされる**（`registerOAuthRoutes()` の手動呼び出し時も同様）。`route:cache` 環境で OAuth callback を使う場合はアプリ側 routes ファイルへ明示登録し、throttle 適用を `route:list` 等で確認すること。

## 秘密情報の漏洩防止

API token / client_secret / refresh_token がログ・例外・debug 情報に出ないようにする。

### ログ漏洩対策

Laravel HTTP Client は `withMiddleware()` または `dump()` / `log()` でリクエストヘッダーをダンプできるが、本パッケージの利用箇所では次を保証する。

- `ChatworkPendingRequestFactory` の標準ビルダーは `dump()` / `log()` を呼ばない。
- 利用者が `Log::channel(...)` で HTTP Client middleware を仕込んだ場合に備え、認証ヘッダーは redact 対象として明文化する（README に注意書きを書く）。
- `ChatworkRequestException::body()` は JSON body の `access_token` / `refresh_token` / `client_secret` キーを `"***"` に置換してから保持する。

### 例外メッセージ

`ChatworkRequestException` / `ChatworkAuthenticationException` / `ChatworkValidationException` の `getMessage()` には token 値を含めない。トークン由来の情報を出す場合は最後 4 文字のみ（例: `"***abc1"`）。

## TokenRepository

```php
interface TokenRepository
{
    public function save(TokenSet $tokenSet, array $context = []): void;

    public function find(string $connectionName): ?TokenSet;
}
```

`TokenRepository` はパッケージがDB migrationを強制しないための境界である。
必要になった時点で、任意のdatabase implementationを追加できるようにする。

## StateStore

OAuth2 callbackのCSRF対策としてstate検証を必須にする。

```php
interface StateStore
{
    public function put(string $state, array $payload, int $ttlSeconds): void;

    public function pull(string $state): ?array;
}
```

初期実装はLaravel cacheベースを候補にする。

