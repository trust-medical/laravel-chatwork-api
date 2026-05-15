# Request / Response設計

## HTTP Client

Laravel HTTP Clientを利用する。
これにより `Http::fake()` でテストしやすくする。

## Base URI

デフォルト:

```text
https://api.chatwork.com/v2
```

参照: https://developer.chatwork.com/docs/endpoints

## 共通Header

```text
Accept: application/json
```

API Tokenの場合:

```text
x-chatworktoken: {token}
```

OAuth2 Bearer Tokenの場合:

```text
Authorization: Bearer {token}
```

## Form Request

Chatwork公式説明に従い、POST/PUTの通常リクエストは `application/x-www-form-urlencoded` を使う。

Laravel HTTP Clientでは `asForm()` を使う。

対象例:

- `POST /rooms/{room_id}/messages`
- `PUT /rooms/{room_id}/messages/{message_id}`
- `POST /rooms`
- `PUT /rooms/{room_id}`
- `POST /rooms/{room_id}/tasks`

### パラメータエンコード規約

`asForm()` に PHP の array を渡すとデフォルトでは `key[0]=...&key[1]=...` の形式になり、Chatwork APIは 400 で返す。次の規約をRequest DTO層で適用する（Resource実装より前で string 化を完了する）。

| 型 | エンコード | 例 |
| --- | --- | --- |
| `csv_integer_list`（`members_admin_ids` 等） | `implode(',', $ints)` で string 化 | `[101, 102]` → `"101,102"` |
| `bool` | `0` / `1` の int 化 | `true` → `1`、`false` → `0` |
| `enum` | `value` を string で送信 | `IconPreset::Group` → `"group"` |
| `null` | 送信しない（payload から除外） | — |
| `string` / `int` | そのまま | — |

責務分割:

- **Request DTO** が上記の型変換と `null` 除外を行う。`toArray()` の戻り値は `array<string, string|int>` のみとし、ネストや bool は含まない。
- **Resource method** は Request DTO の `toArray()` をそのまま `asForm()` に渡す。
- **`ChatworkPendingRequestFactory`** は認証ヘッダー付与・base URI 設定・timeout 適用までを担当し、payload を変換しない。

### bool / int パラメータ表（主なもの）

| パラメータ | 期待値 | 対応Resource |
| --- | --- | --- |
| `force` | `0` / `1` | `messages.list`, `messages.markAsRead` |
| `self_unread` | `0` / `1` | `messages.create` |
| `assigned_by_account_id` | `int` | `tasks.list` |
| `create_download_url` | `0` / `1` | `files.find` |

## Multipart Request

ファイルアップロードでは multipart を使う。

対象:

- `POST /rooms/{room_id}/files`

制約:

- `file` は必須。
- 公式Reference上のファイル上限は5MB。Request DTO 層で `filesize()` または stream の長さで事前検証し、超過は `ChatworkValidationException`。
- `message` は任意、1から65535文字。

## Query Request

GETでquery parameterがあるエンドポイントは、null値を送らない。

例:

- `GET /rooms/{room_id}/messages?force=1`
- `GET /rooms/{room_id}/files?account_id=123`
- `GET /rooms/{room_id}/files/{file_id}?create_download_url=1`

## Path Parameter

path parameterはresource methodの引数で受け取り、URL組み立て前に型と空値を検証する。

例:

```php
Chatwork::rooms()->messages()->find($roomId, $messageId);
```

## Response Mapping

HTTP応答は `ResponseMapper` が戻り値モードに応じて変換する。

```text
HTTP Response
  -> ResponseMapper
    -> array / DTO / Collection / Laravel Response / PSR-7 Response / Result
```

## エラーBody

Chatwork API のエラーボディは2系統ある。`ChatworkRequestException` と `ChatworkResult` は両方を取り出せる API を提供する。

### 通常API（`errors` 配列）

```json
{
  "errors": ["This room is not found."]
}
```

公開API:

```php
$e->errors(); // string[]
```

### OAuthエンドポイント（`error` / `error_description`）

```json
{
  "error": "invalid_grant",
  "error_description": "Refresh token expired."
}
```

公開API:

```php
$e->error();            // ?string
$e->errorDescription(); // ?string
```

### 共通アクセサ

```php
$e->status();        // int
$e->method();        // 'POST'
$e->path();          // '/rooms/123/messages'
$e->operationId();   // 'create_message'
$e->body();          // 生 JSON（秘密情報 redact 後）
$e->rateLimit();     // ?array  ['limit' => 200, 'remaining' => 5, 'reset' => 1735718400]
```

`rateLimit()` は HTTP レスポンスヘッダー `x-ratelimit-limit` / `x-ratelimit-remaining` / `x-ratelimit-reset` が存在するときだけ array を返す（429時に必ず付与される想定）。

## 429 Rate Limit

retry は実装しない。429 / 5xx / ネットワークエラーは戻り値モードに応じて次のように扱う。

| Mode | 挙動 |
| --- | --- |
| `asArray` / `asDto` / `asCollection` | `ChatworkRequestException` を throw（`$e->rateLimit()` で残量取得） |
| `asResponse` | Laravel Response をそのまま返す（`$response->header('x-ratelimit-reset')` で確認） |
| `asPsrResponse` | PSR-7 Response をそのまま返す |
| `asResult` | 失敗 `ChatworkResult` を返す（`$result->rateLimit()` で取得） |

利用側が rate limit に応じた backoff を実装するための情報を必ず提供する。パッケージ側で sleep / retry はしない。

## Timeout

`config/chatwork.php` の `timeout` を `ChatworkPendingRequestFactory` が `Http::timeout()` に渡す。デフォルトは 10 秒。

```php
'timeout' => env('CHATWORK_TIMEOUT', 10),
```

リクエスト単位の上書きは `Http` のチェーンに頼らず、Manager から `withTimeout($seconds)` を提供する（Phase 6+ で対応、初期実装ではconfig値のみ）。

## User-Agent

`ChatworkPendingRequestFactory` は次の形式の `User-Agent` ヘッダーを送る。

```
trust-medical/laravel-chatwork-api/{version} Laravel/{laravel_version} PHP/{php_version}
```

`{version}` は `composer.json` の `version` または Composer の installed-versions API から取得する。利用者の運用調査やChatwork側のサポート問い合わせで識別可能にするため。

