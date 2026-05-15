---
paths:
  - "src/**/*.php"
---

# アーキテクチャ不変条件

このルールは `src/**` 編集時に守るべき構造的制約。`docs/03-package-architecture/` の設計書と一致させる。

## ライブラリ API 確認の流儀

Laravel / Pest / PHPStan / Larastan / spatie/laravel-package-tools / Orchestra Testbench 等の **外部ライブラリの API 仕様や設定** を確認する場合は、推測せず `context7` plugin（`enabledPlugins` で有効化済み）を使う:

1. `/docs <library-name>` スキル経由、または context7 が提供するツールでライブラリ ID を解決
2. 該当 API のドキュメントを取得
3. バージョンを明示する（例: Laravel 12.x、Pest 3.x、Larastan 3.x）

訓練データの古いバージョンと現行版で API が異なるケースがあるため、特に以下では必ず確認:
- `Illuminate\Http\Client\Factory` (`Http::fake()` の戻り値型・assertion)
- `spatie\LaravelPackageTools\Package` (`configurePackage` の chain API)
- `Orchestra\Testbench\TestCase` (Laravel メジャー毎の差異)
- Pest expectations / hook API

## 型エラー検知の二重化

PHP コード編集時の品質ゲートは 2 段構え:

1. **LSP（Intelephense, `php-lsp@claude-plugins-official`）**: 編集後即座に diagnostic を返す。型不一致 / undefined symbol / missing import を Claude が同じターン内に検知し修正できる。`Ctrl+O` で確認可能。
2. **PHPStan PostToolUse hook (`phpstan-on-src.sh`)**: level 6 で再チェック。LSP が見逃した template / generics エラーをカバー。`hookSpecificOutput.additionalContext` で Claude に返却。

両方の警告に対応してから Pest を実行する習慣にすること。LSP の即時性に頼って PHPStan の指摘を無視しない。

## 依存方向（一方向）

```
Facade (Chatwork::)
  → ChatworkManager        connection 解決・client 生成
    → Connection           値オブジェクト（config/DB/動的 token を統一表現）
    → ChatworkClient       低レベル HTTP 実行・resource factory
      → Resources/*        エンドポイント単位の公開 API
      → ResponseMapper     戻り値モードに応じたレスポンス変換

Notification
  → ChatworkChannel
    → ChatworkManager
    → RoomMessagesResource
```

**逆方向の依存は禁止**。Resources から Manager を直接見ない、Notification から HTTP を直接触らない。

## 各層の責務

| 層 | 責務 | 禁止 |
|---|---|---|
| `ChatworkManager` | connection 名 → `Connection` の解決、`ChatworkClient` の組み立て | HTTP を直接実行 |
| `Connection` | base URI / credentials / TokenProvider の保持 | 副作用を持つメソッド |
| `ChatworkClient` | Resource factory、低レベル HTTP 実行 | ビジネスロジック |
| `Resources/*` | エンドポイント単位の公開 API（fluent chain の起点） | request 構築の重複 → `Data/Requests/*` に委譲 |
| `Data/Requests/*` | 送信前バリデーション、payload 構築 | HTTP 実行 |
| `Data/Responses/*` | readonly DTO | mutator |
| `Http/ResponseMapper` | HTTP response → 戻り値モードに応じた変換 | エンドポイント固有のロジック |
| `Notifications/*` | Laravel Notification 連携のみ | HTTP を直接触る |

## Connection / TokenProvider

- `Connection` は値オブジェクト。同じ name + credentials なら同一とみなせる。
- DB やシークレットマネージャからトークンを動的に取得する場合は `TokenProvider` 実装を作り、`Credentials::fromProvider($provider)` で組み立てる。
- `TokenProvider` は `credentials(): Credentials` 一つだけ。状態を持たない実装が望ましい。

## 戻り値モード

利用側はチェーンで戻り値モードを選ぶ。デフォルトは `asDto()`。

- `asArray()` / `asDto()` / `asCollection()`: 失敗時に `ChatworkRequestException`
- `asResponse()` / `asPsrResponse()`: throw しない、Laravel/PSR-7 response を返す
- `asResult()`: throw しない、`ChatworkResult` で成功失敗を値として表現

**送信前バリデーション失敗は戻り値モードを無視して常に `ChatworkValidationException` を throw する**。

## OAuth2

- `Auth/OAuth/*` に隔離する。通常 API クライアントと責務を混ぜない。
- callback route はデフォルト無効（`oauth.routes_enabled: false`）。
- state 検証は必須。`StateStore` で抽象化する。
- `TokenRepository` で保存先を差し替え可能にする。パッケージは DB migration を強制しない。

## Notification

- `ChatworkChannel::send()` の処理順:
  1. notification から `toChatwork($notifiable)` で `ChatworkMessage` 取得
  2. `ChatworkRoute` 解決（明示 `toRoom()` > `routeNotificationForChatwork()` > `Notification::route()`）
  3. `ChatworkManager` から connection 付き client 取得
  4. `POST /rooms/{room_id}/messages` 実行
  5. レスポンスを Laravel `NotificationSent` event 用に返却
- channel は `asDto()` 相当で送信する。失敗は `ChatworkRequestException`。

## HTTP

- Laravel HTTP Client (`Illuminate\Support\Facades\Http`) を使う。Guzzle 直叩きは避ける。
- POST/PUT 通常リクエストは `asForm()`（`application/x-www-form-urlencoded`）。
- ファイルアップロードは multipart。
- retry / rate limit 制御は実装しない。429/5xx は戻り値モードで処理。
- base URI のデフォルトは `https://api.chatwork.com/v2`。config で変更可能。
