# 機能要件

## APIクライアント

- Chatwork API v2の全エンドポイントに対応する。「全エンドポイント対応」の断面は `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `paths` 全件を指す（補完済みOpenAPIが正、追加・廃止はこのファイルに同期する）。
- 初期実装は `POST /rooms/{room_id}/messages` を対象にする。
- Facade、DI、resource chain、簡易メソッドのすべてを提供する。
- `POST` / `PUT` の通常リクエストは `application/x-www-form-urlencoded` を基本にする。
- ファイルアップロードは multipart を使う。
- 破壊的操作は明示的なメソッド名にする。

例:

```php
Chatwork::rooms()->leaveRoom($roomId);
Chatwork::rooms()->deleteRoom($roomId);
Chatwork::rooms()->messages()->deleteMessage($roomId, $messageId);
```

## 認証

- API Token認証に対応する。
- OAuth2 Bearer Token認証に対応する。
- OAuth2の認可URL生成、callback処理、refresh token更新を提供する。
- config connection、リクエスト時トークン指定、動的 `TokenProvider` を提供する。

## 複数ワークスペース

config上の connection と、アプリケーション側で動的に組み立てる `Connection` の両方を扱う。

```php
Chatwork::connection('sales')->rooms()->messages()->create($roomId, '本文');
Chatwork::withApiToken($token)->rooms()->messages()->create($roomId, '本文');
Chatwork::forConnection($connection)->rooms()->messages()->create($roomId, '本文');
```

## Laravel Notifications

Laravel 11から13のNotification機構に準拠する。
custom channel として `ChatworkChannel` を提供する。

対応する送信方法（`$notification` は `ChatworkNotification` を継承し `toChatwork(): ChatworkMessage` を実装した通知。`ChatworkMessage` 自体は通知ではない）:

```php
$user->notify($notification);
```

```php
Notification::route('chatwork', $roomId)
    ->notify($notification);
```

```php
Notification::send($users, $notification);
```

## Message Builder

`ChatworkMessage` は fluent API を提供する。

```php
ChatworkMessage::make()
    ->body('本文')
    ->toRoom($roomId)
    ->selfUnread();
```

初期対応するChatwork記法:

- `[To:{account_id}]`
- `[info][title]...[/title]...[/info]`
- `[code]...[/code]`
- 罫線

`info()` / `plain()` / `escape()` に渡したテキストは角括弧を全角へ無害化する。
Chatwork記法をそのまま送るのは `body()` を明示した場合だけ。

## 戻り値

利用側が戻り値モードを指定できる。

```php
Chatwork::asArray();
Chatwork::asDto();
Chatwork::asCollection();
Chatwork::asResponse();
Chatwork::asPsrResponse();
Chatwork::asResult();
```

デフォルトは `asDto()` とする。

### throw / not-throw 契約

戻り値モードごとの4xx/5xx時の挙動は次の通り。送信前バリデーションの失敗は戻り値モードに関係なく `ChatworkValidationException` を投げる。

| Mode | 4xx / 5xx |
| --- | --- |
| `asArray` | `ChatworkRequestException` を throw |
| `asDto` | `ChatworkRequestException` を throw |
| `asCollection` | `ChatworkRequestException` を throw |
| `asResponse` | throw しない（Laravel Response を返す） |
| `asPsrResponse` | throw しない（PSR-7 Response を返す） |
| `asResult` | throw しない（失敗 `ChatworkResult` を返す） |

詳細は `docs/03-package-architecture/response-strategy.md`。

## エラーモデル

例外は2系統のみ。

| 例外 | 発火タイミング | 投げる側 |
| --- | --- | --- |
| `ChatworkValidationException` | 送信前のクライアント側検証で失敗 | Request DTO / Resource method / Message builder |
| `ChatworkRequestException` | HTTP 4xx / 5xx（`asArray` / `asDto` / `asCollection` のみ） | `ResponseMapper` |

`ChatworkValidationException` の発火条件は以下に限定する（HTTPは発生しない）。

- 必須パラメータ欠落（例: message body の空）
- 文字数制約超過（例: message body 65535文字超）
- ファイルサイズ制約超過（例: file upload 5MB超）
- 許可されないenum値
- 配列要素の型不一致（例: `members_admin_ids` に非整数）

Chatwork API側のエラーボディ形式は2系統あり、`ChatworkRequestException` は両方を取り出せる。

- 通常API: `{ "errors": ["..."] }`（`errors(): string[]`）
- OAuth: `{ "error": "...", "error_description": "..." }`（`error(): ?string`、`errorDescription(): ?string`）

例外には次を含める。秘密情報（API token / client_secret / refresh_token）は除外する。

- status code
- response body（redact 後）
- request method / path / operationId
- parsed errors / error_description
- `x-ratelimit-*` ヘッダ（429時）

## バリデーション

DTOまたはrequest object生成時に、API制約を超える明らかな値を送信前に検出する。

例:

- message body: 1から65535文字
- task body: 1から65535文字
- file upload: 5MB上限
- enum: Chatwork APIが定義する許可値

数値制約の出典は以下のいずれかを優先する。

1. `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `maxLength` / `maximum` / `enum`
2. Chatwork公式Reference: https://developer.chatwork.com/reference
3. Chatwork API Documentation PDF: https://download.chatwork.com/ChatWork_API_Documentation.pdf

出典が変動する場合は補完済みOpenAPIを正として更新し、Resource実装に反映する。

## Chatwork記法のスコープ境界

`ChatworkMessage` builder は次に限定する（MVP）。

- 対応: `[To:{account_id}]` / `[info][title]...[/title]...[/info]` / `[code]...[/code]` / 罫線
- 非対応（初期実装外）: `[rp]` 返信、引用、絵文字、`[piconname:]` 等の装飾記法

単独の `[title]...[/title]` はChatwork側で装飾されないため、ビルダーからは提供しない（`info()` の内側でのみ意味を持つ）。

本文に直接Chatwork記法を書いた場合の挙動は次の通り。

| 呼出 | 本文の扱い |
| --- | --- |
| `body($text)` | そのまま送信する（記法は有効） |
| `info($title, $body)` | タイトル・本文を無害化してから `[info]` 枠で囲む |
| `plain($text)` / `escape($text)` | Chatwork記法を無効化する（`[` / `]` 等を文字として扱う） |

`plain()` と `escape()` の実体は同じ。`plain()` は意図の明示、`escape()` は危険文字列処理の文脈で使う。

## 受け入れ基準（Phaseトレーサビリティ）

各機能要件は `docs/06-testing/tdd-roadmap.md` の Phase に対応する。実装完了の判定は次のテストが緑になることで行う。

| 要件 | Phase | 主な受け入れ条件 |
| --- | --- | --- |
| API Token認証 | Phase 1 | `withApiToken()` で `x-chatworktoken` ヘッダーが送信される |
| OAuth2 Bearer認証 | Phase 1 | `withBearerToken()` で `Authorization: Bearer` ヘッダーが送信される |
| `POST /rooms/{room_id}/messages` | Phase 2 | `asDto()` で `CreatedMessage` 返却、400で `ChatworkRequestException`、`asResponse()/asResult()` は throw しない |
| Notification経由送信 | Phase 3 | `$user->notify($notification)`（`ChatworkNotification` 経由）でPOSTされる、戻り値モードは `asResult()` 固定 |
| OAuth2フロー | Phase 4 | authorization URL生成、callback state検証、refresh tokenによる更新が `Http::fake()` でテスト可能 |
| Messages残API | Phase 5 | list / find / update / delete / markAsRead / markAsUnread のすべてのテストが緑 |
| 全エンドポイント | Phase 6+ | rooms / members / tasks / files / links / contacts / me / my / incoming_requests を順次対応 |

Phaseごとの詳細は `docs/06-testing/tdd-roadmap.md` を参照。

## オンデマンドtokenのスコープ

`withApiToken()` / `withBearerToken()` / `forConnection()` はチェーンに対する一時上書きであり、Manager のグローバル状態を変更しない。

- `Chatwork::withApiToken($t)->rooms()->messages()->create(...)` は当該チェーンのみに有効。
- 次の呼び出し `Chatwork::rooms()->...` は `default` connection に戻る。
- 解決の優先順位は `withApiToken / withBearerToken / forConnection > connection('name') > default connection`。

## OAuth2 callbackのHTTP仕様

callback routeは `'oauth.routes_enabled' => true` のときだけ登録される。

```
GET /chatwork/oauth/callback
  Query: code, state, [error, error_description]
```

| ケース | レスポンス |
| --- | --- |
| 成功（code受領 + state検証成功） | `TokenRepository::save()` 後、設定された redirect 先へ 302 |
| state不一致 / state期限切れ | 400 を返し、token endpoint には送信しない |
| `error` パラメータ受領 | 400 を返し、`error_description` をログに出力（秘密情報は出さない） |
| `TokenRepository` 未設定 | 500（設定不備として例外） |

route prefix は `'oauth.route_prefix'` で変更可能。複数のcallback先が必要な場合は、利用者側で controller を継承して上書きできる。

