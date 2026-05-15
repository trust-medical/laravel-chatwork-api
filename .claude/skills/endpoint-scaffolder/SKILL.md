---
name: endpoint-scaffolder
description: 指定された Chatwork API operationId に対して Resource クラス / Request DTO / Response DTO / Pest テストの骨組み4ファイルを一括生成するスキル。手動呼び出し専用（`/endpoint-scaffolder <operationId>`）。生成後は Red 状態のテストが残り、その後 Green 実装に進む TDD ワークフロー前提。
argument-hint: <operationId>
arguments: operationId
disable-model-invocation: true
allowed-tools: Read, Write, Glob, Grep, Bash
context: fork
agent: general-purpose
---

# Chatwork Endpoint Scaffolder

operationId `$operationId` に対応する 4 ファイルの骨組みを生成します。

## 入力検証

- `$operationId` が空なら処理を中断し、利用可能な operationId 一覧表示のヒントを返す。

## 手順

### 1. OpenAPI から情報抽出

優先順位:
1. `openapi-chatwork` MCP が起動中なら `mcp__openapi-chatwork__*` を使い operation 単位で取得
2. 未起動なら `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` を直接 Read

`$operationId` に対応する以下を取得:

- HTTP method
- path（`/rooms/{room_id}/messages` 等）
- parameters（path / query）
- requestBody schema
- responses の status code 一覧と example
- 分類するリソース名（`rooms`, `messages`, `tasks`, `members`, `files`, `links`, `me`, `my`, `contacts`, `incoming_requests`）

### 2. 命名の決定

- Resource クラス名: `<Subject>Resource`（例: `RoomMessagesResource`）
- メソッド名: 動詞ベース（`create` / `list` / `find` / `update` / `delete` / `leave` / `replace` / `markAsRead` / `markAsUnread` / `upload` / `accept` / `decline`）
- 破壊的操作は曖昧短名を避ける（`leaveRoom`, `deleteRoom`, `replaceMembers`, `deleteMessage`, `decline` 等）
- Request DTO: `<Operation>Request`（必要な場合のみ）
- Response DTO: `<Subject>Data` または成功時の意味的名前

参考: `.claude/rules/coding-style.md`, `docs/04-api-client/resources-and-methods.md`

### 3. 生成するファイル

以下 4 ファイルを **存在しない場合のみ** 作成。存在する場合は警告して上書きしない。

1. **Resource クラス** `src/Resources/<ResourceName>.php`
   - メソッドシグネチャと PHPDoc のみ。実装本体は `throw new \LogicException('Not implemented yet (Red phase).');` で仮実装。
   - 認証ヘッダー処理 / HTTP 実行 / ResponseMapper 呼び出しは含めない（Green フェーズで書く）。

2. **Request DTO**（requestBody がある場合のみ） `src/Data/Requests/<RequestName>.php`
   - `readonly class`、constructor で全フィールド受け取り
   - 各フィールドに PHP の型 + PHPDoc で OpenAPI 制約（max length 等）をコメント
   - バリデーションメソッドは骨格のみ（実装は Green フェーズ）

3. **Response DTO** `src/Data/Responses/<ResponseName>.php`
   - `readonly class`
   - OpenAPI の success response schema からプロパティを抽出

4. **Pest テスト** `tests/Feature/Resources/<ResourceName>Test.php`
   - `pest-test-writer` agent と同等の構造（Red 段階で書く）
   - URL / method / 認証 / payload / 戻り値 / バリデーション / 4xx を網羅

### 4. Fixture 生成

`responses.*.content.application/json.example` を `tests/Fixtures/chatwork/<resource>/<operation>-<status>.json` に保存（既存ならスキップ）。

### 5. 出力サマリ

```markdown
## 生成完了: <operationId>

- src/Resources/<ResourceName>.php
- src/Data/Requests/<RequestName>.php
- src/Data/Responses/<ResponseName>.php
- tests/Feature/Resources/<ResourceName>Test.php
- tests/Fixtures/chatwork/<resource>/<op>-<status>.json

次のステップ:
1. `composer test` を実行して Red を確認
2. Resource クラスの本体実装（Green フェーズ）
3. `composer run ci` で全チェックが通ることを確認
```

## 制約

- 既存ファイルは絶対に上書きしない。
- `.claude/rules/coding-style.md` の命名・declare(strict_types=1) を守る。
- ServiceProvider やルーティング設定には触れない。
- Resource クラスは ChatworkClient を依存注入する前提（実装本体は仮）。

## エラー時

- operationId が OpenAPI に無ければ: `「operationId '$operationId' が docs/02-openapi/chatwork-api-v2-complemented.openapi.json に見つかりません」` と返す。
- リソース分類が不明: `docs/04-api-client/resources-and-methods.md` を再読してから手動で振り分けるよう促す。
