---
name: fixture-from-openapi
description: 補完済み OpenAPI 仕様の `responses.*.content.application/json.example` を抽出し、tests/Fixtures/chatwork/<resource>/<operation>-<status>.json として保存するスキル。手動呼び出し専用（`/fixture-from-openapi <operationId>`）。Pest テストで Http::fake() に食わせる fixture を OpenAPI から機械的に同期する。
argument-hint: <operationId>
arguments: operationId
disable-model-invocation: true
allowed-tools: Read, Write, Glob, Bash
context: fork
agent: general-purpose
---

# Fixture Generator from OpenAPI

operationId `$operationId` の全 status code に対応する fixture JSON を生成します。

## 入力検証

- `$operationId` が空なら、利用可能な operationId の取得方法をヒント表示。

## 手順

### 1. OpenAPI 抽出

`openapi-chatwork` MCP が起動中なら `mcp__openapi-chatwork__*` で operation 単位取得（token 効率）。未起動なら `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` を直接 Read。

`$operationId` を検索し:

- リソース分類（path から推測：`/rooms/...` → `rooms`、`/rooms/.../messages/...` → `messages` 等）
- 全 `responses.<status>.content.application/json.example`（200/201/204/400/401/403/404/429 等）

### 2. ファイルパス決定

```
tests/Fixtures/chatwork/<resource>/<operation>-<status>.json
```

- `<resource>`: messages / rooms / members / tasks / files / links / contacts / me / my / incoming_requests
- `<operation>`: operationId（snake_case のまま、または短縮形）
- `<status>`: HTTP status code（200, 400 等）

### 3. JSON 整形と保存

- example をそのまま整形して保存（`JSON_PRETTY_PRINT` 相当の 4 スペースインデント）。
- 既存ファイルは **上書きしない**（警告のみ）。
- example が空または存在しない status code はスキップ。

### 4. 204 の扱い

204 No Content は body が空。fixture ファイルは作らないが、サマリに「204: no body」と記録する。

### 5. 出力サマリ

```markdown
## Fixture 生成完了: <operationId>

### 新規作成
- tests/Fixtures/chatwork/messages/create_message-200.json
- tests/Fixtures/chatwork/messages/create_message-400.json

### スキップ（既存）
- tests/Fixtures/chatwork/messages/create_message-401.json

### スキップ（example なし）
- 503

### 204 No Content
- なし

### 使い方
```php
$this->fixtureJson('messages/create_message-200.json');
```
```

## 制約

- OpenAPI example を **改変しない**（テストの真実性を保つため）。
- fixture ディレクトリが無ければ作成する。
- 既存 fixture と内容が異なる場合は警告するが、上書きしない。
