# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

`trust-medical/laravel-chatwork-api` は Chatwork API v2 を Laravel から安全に利用するための Composer パッケージ。
Facade / DI / Laravel Notification の3経路を公式サポートする。

- PHP: `^8.3`
- Laravel: `^11.0 || ^12.0 || ^13.0`
- Chatwork API: v2（Base URI: `https://api.chatwork.com/v2`）

## 開発コマンド

```bash
# テスト実行（全件）
./vendor/bin/pest

# テスト実行（1ファイル）
./vendor/bin/pest tests/Feature/RoomMessagesTest.php

# コードスタイル修正
./vendor/bin/pint

# 静的解析（導入後）
./vendor/bin/phpstan analyse
```

## アーキテクチャ

### 依存方向

```
Facade (Chatwork::)
  → ChatworkManager        # connection解決・client生成
    → Connection           # 値オブジェクト（config/DB/動的 token を統一表現）
    → ChatworkClient       # 低レベル HTTP 実行・resource factory
      → Resources/*        # エンドポイント単位の公開API
      → ResponseMapper     # 戻り値モードに応じたレスポンス変換

Notification
  → ChatworkChannel
    → ChatworkManager
    → RoomMessagesResource  # HTTP詳細を直接知らない
```

### src/ 構成

```
src/
  ChatworkServiceProvider.php
  ChatworkManager.php
  ChatworkClient.php
  Connection.php
  Facades/Chatwork.php
  Auth/
    ApiTokenCredentials.php
    BearerTokenCredentials.php
    Credentials.php
    TokenProvider.php          # interface: credentials() を返す
    OAuth/
      OAuthClient.php
      TokenSet.php
      TokenRepository.php      # interface: save() / find()
      StateStore.php           # interface: put() / pull()
      Controllers/OAuthCallbackController.php
  Data/
    Requests/                  # immutable な request value object
    Responses/                 # readonly DTO
  Enums/
  Exceptions/
    ChatworkRequestException.php
    ChatworkValidationException.php
    ChatworkAuthenticationException.php
    ChatworkRoutingException.php       # 通知ルート衝突用（ChatworkValidationException 継承）
  Http/
    ChatworkPendingRequestFactory.php
    ResponseMapper.php
    Result.php                 # asResult() 用の成功/失敗値
  Notifications/
    ChatworkChannel.php
    ChatworkNotification.php
    ChatworkMessage.php        # message builder / DTO（Notification ではない）
    ChatworkRoute.php
  Resources/
    RoomsResource.php
    RoomMessagesResource.php
    RoomMembersResource.php
    RoomTasksResource.php
    RoomFilesResource.php
    RoomLinksResource.php
    MeResource.php
    MyResource.php
    ContactsResource.php
    IncomingRequestsResource.php
```

## 主要な設計決定

### 戻り値モード

デフォルトは `asDto()`。利用側がチェーンで変更できる。

| Mode | 成功時 | 4xx/5xx |
|---|---|---|
| `asArray()` | 配列 | `ChatworkRequestException` |
| `asDto()` | readonly DTO | `ChatworkRequestException` |
| `asCollection()` | `Collection` | `ChatworkRequestException` |
| `asResponse()` | Laravel Response | throwしない |
| `asPsrResponse()` | PSR-7 Response | throwしない |
| `asResult()` | `ChatworkResult` | throwしない |

送信前バリデーション失敗は戻り値モードに関わらず `ChatworkValidationException`。

Notification 経由（`ChatworkChannel`）の送信は **`asResult()` 固定**。4xx は permanent failure として例外、5xx / 429 / network error は queue retry に委譲する。

### エラーボディ

Chatwork API のエラーボディは2系統あり、`ChatworkRequestException` は両方を取り出せる。

- 通常 API: `{ "errors": ["..."] }` → `$e->errors(): string[]`
- OAuth: `{ "error": "...", "error_description": "..." }` → `$e->error()` / `$e->errorDescription()`

`$e->rateLimit()` は 429 時に `x-ratelimit-limit` / `x-ratelimit-remaining` / `x-ratelimit-reset` を返す。

### 認証

- API Token → `x-chatworktoken: {token}` ヘッダー
- OAuth2 Bearer Token → `Authorization: Bearer {token}` ヘッダー
- 1リクエストで両方を同時に送らない。排他は `Credentials` 実装 (`ApiTokenCredentials` / `BearerTokenCredentials`) で構造的に保証する。
- `TokenProvider` interface により DB / KMS / refresh 済み token を動的に解決できる
- OAuth refresh は `Cache::lock` で多重発行を防止する

### HTTP リクエスト形式

- POST / PUT（通常）: `application/x-www-form-urlencoded`（Laravel `asForm()`）
- ファイルアップロード: multipart（`POST /rooms/{room_id}/files`）
- GET: query parameter（null 値は送らない）
- 配列/bool/enum は Request DTO 層で string 化（`csv_integer_list` は `implode(',', ...)`、bool は `0`/`1`、enum は `value`）
- `User-Agent: trust-medical/laravel-chatwork-api/{version} Laravel/{version} PHP/{version}` を付与
- retry / rate limit 制御は実装しない。429/5xx は戻り値モードで処理。

### 破壊的操作の命名規則

曖昧な短名（`delete()`）は使わない。

```php
Chatwork::rooms()->leaveRoom($roomId);      // action_type=leave
Chatwork::rooms()->deleteRoom($roomId);     // action_type=delete
Chatwork::rooms()->members()->replaceMembers($roomId, $request);
Chatwork::incomingRequests()->decline($requestId);
```

### 型安全性

- Response DTO は `readonly class`
- Request object は可能な限り immutable
- PHP enum を積極的に使う

## テスト方針

- テストフレームワーク: **Pest** + **Orchestra Testbench**
- 実 API は叩かない。すべて `Http::fake()` で検証。
- `Http::preventStrayRequests()` を必ず使い、fake し忘れた通信を即失敗させる。

### fixture 配置

```
tests/Fixtures/chatwork/
  messages/
    create-message-201.json     # 実 status code を使う
    create-message-400.json
    create-message-429.json
  oauth/
    token-200.json
    token-400.json
```

ファイル名は `{operation-kebab}-{actual-status-code}.json`。fixture は `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の response example を参照して作る。詳細は `docs/06-testing/http-fake-strategy.md`。

## API 仕様ソース

実装時の参照優先順位：

1. Chatwork 公式 Reference: https://developer.chatwork.com/reference
2. `docs/02-openapi/chatwork-api-v2-complemented.openapi.json`
3. `docs/02-openapi/normalized-chatwork-api-v2.yaml`

## セキュリティ

- API token / client secret / refresh token をログ・例外メッセージに含めない
- OAuth2 callback では state 検証を必須にする（`StateStore`）
- OAuth2 callback route はデフォルト無効（`oauth.routes_enabled: false`）

## 開発時のルール

実装・テスト記述・コミット時のルールは `.claude/rules/` 配下に分割されており、Claude Code が自動 discovery する。`paths` frontmatter により対象ファイルを編集するときだけ context にロードされる。

- `.claude/rules/coding-style.md` — PHP コーディング規約（`src/**/*.php`, `tests/**/*.php`）
- `.claude/rules/testing.md` — Pest / Http::fake() / fixture 配置（`src/**/*.php`, `tests/**/*.php`）
- `.claude/rules/architecture.md` — 層の依存方向と不変条件（`src/**/*.php`）
- `.claude/rules/commit-style.md` — Conventional Commits と TDD コミット粒度（無条件ロード）
