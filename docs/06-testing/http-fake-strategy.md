# Http::fake() テスト方針

## 目的

すべてのHTTP通信をLaravel HTTP Clientの `Http::fake()` で検証する。
実APIを叩くテストは作らない。

## 基本形

```php
Http::fake([
    'https://api.chatwork.com/v2/rooms/123/messages' => Http::response([
        'message_id' => '456',
    ], 200),
]);
```

送信内容の検証:

```php
Http::assertSent(function (Request $request) {
    return $request->url() === 'https://api.chatwork.com/v2/rooms/123/messages'
        && $request->method() === 'POST'
        && $request->hasHeader('x-chatworktoken', 'token')
        && $request['body'] === '本文'
        && $request['self_unread'] === 1;
});
```

## 認証テスト

API Token:

```text
x-chatworktoken: token
```

OAuth2:

```text
Authorization: Bearer token
```

同一リクエストで両方を同時に送らないことを検証する。

## Form送信テスト

`POST` / `PUT` の通常リクエストでは、form requestとして送られることを検証する。
Laravel HTTP Clientのrequest内容からpayloadを確認する。

## Multipartテスト

`POST /rooms/{room_id}/files` では、実ファイルではなくテスト用の小さなstreamまたはfake uploadを使う。
5MB超過は送信前validationで落とす。

## OAuth2テスト

token endpoint:

```php
Http::fake([
    'https://oauth.chatwork.com/token' => Http::response([
        'access_token' => 'access',
        'refresh_token' => 'refresh',
        'expires_in' => 3600,
        'token_type' => 'Bearer',
    ], 200),
]);
```

検証項目:

- `grant_type=authorization_code`
- `grant_type=refresh_token`
- client secretをログや例外に出さない
- state不一致ではtoken endpointに送信しない

## Fixture

fixtureは `tests/Fixtures/chatwork` 配下に置く想定。

### 命名規則

```text
tests/Fixtures/chatwork/
  messages/
    create-message-201.json   # POST /rooms/{room_id}/messages 成功（実 status code）
    create-message-400.json   # 400 Bad Request
    create-message-429.json   # 429 Rate Limit
    list-messages-200.json    # GET /rooms/{room_id}/messages 成功
    list-messages-204.json    # 204 No Content（新着なし）
  oauth/
    token-200.json            # POST /token 成功
    token-400.json            # invalid_grant
```

ルール:

- ファイル名は `{operation-kebab}-{actual-status-code}.json`。`actual-status-code` は Chatwork API が実際に返す数値（200 / 201 / 204 / 400 / 401 / 403 / 404 / 429 / 500）を使う。「success / failure」のような抽象語は使わない。
- `operation-kebab` は OpenAPI の `operationId` を kebab-case 化したもの（`create_message` → `create-message`、`mark_room_messages_as_read` → `mark-read`）。
- ディレクトリは Resource 単位（`messages/`, `rooms/`, `tasks/`, `files/`, `links/`, `members/`, `contacts/`, `me/`, `my/`, `incoming-requests/`, `oauth/`）。

### 出典

fixtureは次の優先順位で値を生成する。

1. `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `responses.*.content.application/json.example` または `examples`
2. Chatwork公式Reference: https://developer.chatwork.com/reference
3. Chatwork API Documentation PDF: https://download.chatwork.com/ChatWork_API_Documentation.pdf

OpenAPI に example が無い場合、Reference / PDF から最小サンプルを起こし、補完済み OpenAPI に example として追加してから fixture を作る（双方向に同期する）。

### 読み込みヘルパ

```php
// tests/Pest.php
function fixture(string $relativePath): string
{
    return file_get_contents(__DIR__.'/Fixtures/chatwork/'.$relativePath);
}

// 利用例
Http::fake([
    'https://api.chatwork.com/v2/rooms/123/messages' => Http::response(
        fixture('messages/create-message-201.json'),
        201,
    ),
]);
```

## 外部通信の禁止

テストでは `Http::preventStrayRequests()` を利用する。
fakeし忘れた外部通信を失敗させる。

