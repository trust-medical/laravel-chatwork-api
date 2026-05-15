---
paths:
  - "src/**/*.php"
  - "tests/**/*.php"
---

# コーディングスタイル規約

このルールは `src/**/*.php` と `tests/**/*.php` の編集時に適用する。

## 言語機能

- 全 PHP ファイルの先頭で `declare(strict_types=1);` を宣言する。
- PHP 8.3 機能（readonly class, constants in trait, json_validate 等）を積極的に使う。
- 名前空間は `TrustMedical\LaravelChatworkApi\` 起点とする。サブ名前空間は `src/` 配下のディレクトリ構造と一致させる。
- 列挙可能な値は backed enum (`enum Foo: string`) で表現する。

## 命名規則

- クラス: PascalCase（`ChatworkClient`、`RoomMessagesResource`）。
- メソッド・プロパティ: camelCase（`createRoomMessage`、`baseUri`）。
- 破壊的操作は曖昧な短名を避け、対象を明示する。
  - 良い例: `leaveRoom($roomId)`, `deleteRoom($roomId)`, `replaceMembers($roomId, $request)`, `deleteMessage($roomId, $messageId)`
  - 悪い例: `delete()`, `remove($id)`
- リソースクラスは `<Domain>Resource` で統一（例: `RoomMessagesResource`）。
- DTO は名詞のまま。Request は `<Operation>Request`、Response は `<Subject>Data` または `<Operation>Result`。

## DTO

- Response DTO は `readonly class` を基本にする。
- Request DTO も可能な限り immutable（constructor で全フィールド受け取り、setter を持たない）。
- enum 化できる値は PHP enum を必ず使う。string 直書きを避ける。
- DTO の constructor 引数は名前付き引数で呼べる順序にする。

## Facade

- `src/Facades/Chatwork.php` には `@method static` の docblock を網羅的に書く。型情報は static analysis のためのもの。
- accessor は `chatwork` で固定。

## エラーハンドリング

- 送信前バリデーション失敗は `ChatworkValidationException`（戻り値モードに関わらず常に throw）。
- HTTP 4xx/5xx は戻り値モードに応じて `ChatworkRequestException` または `Result` で表現する。
- 例外メッセージに **API token / client secret / refresh token / Bearer 値** を含めない。

## コメント

- 何を書いているかを説明するコメントは書かない（識別子の良い命名で代替する）。
- なぜそうしたかが非自明な場合（仕様の制約、ワークアラウンド、既知のバグ回避）にのみ短く書く。
- PHPDoc は static analysis に必要な範囲で書く（generics、array shape など）。
