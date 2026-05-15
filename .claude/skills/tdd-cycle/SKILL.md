---
name: tdd-cycle
description: 指定 operationId に対し Red→Green→Refactor の1サイクルを誘導するスキル。手動呼び出し専用（`/tdd-cycle <operationId>`）。fixture 生成 → Pest テスト記述 → composer test で Red 確認 → 実装 → Green 確認 → Pint/PHPStan/refactor → commit の流れを agent オーケストレーションで進める。
argument-hint: <operationId>
arguments: operationId
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
context: fork
agent: general-purpose
---

# TDD Cycle: $operationId

Chatwork API operationId `$operationId` について、Red→Green→Refactor を 1 サイクル進めます。

## 前提条件チェック

```!
test -f vendor/bin/pest && echo "vendor/bin/pest: OK" || echo "vendor/bin/pest: MISSING — run `composer install` first"
test -f vendor/bin/pint && echo "vendor/bin/pint: OK" || echo "vendor/bin/pint: MISSING"
test -f vendor/bin/phpstan && echo "vendor/bin/phpstan: OK" || echo "vendor/bin/phpstan: MISSING"
```

vendor が不足している場合は中断して `composer install` を促す。

## ステップ

### Step 1: Architect 設計確認

`chatwork-endpoint-architect` agent を `$operationId` で呼び、Resource / DTO / テスト観点の設計案を取得。
ユーザーに設計案を提示し、続行確認を取る。

### Step 2: Fixture 生成（Red 準備）

`/fixture-from-openapi $operationId` 相当の処理で `tests/Fixtures/chatwork/...` に JSON を生成。

### Step 3: Red — Pest テスト記述

`pest-test-writer` agent を呼び、`tests/Feature/Resources/<ResourceName>Test.php` を作成。

実行: `./vendor/bin/pest tests/Feature/Resources/<ResourceName>Test.php`

期待: **テストが失敗する**（実装が無いため）。失敗を確認できたら Step 4 へ。
もし grow（テストが通ってしまう）場合は Red が成立していないので、テスト内容を見直す。

### Step 4: Green — 最小実装

Resource クラス / Request DTO / Response DTO を実装。**テストを通す最小限**に留める。

- Request DTO: バリデーションロジックを書く
- Response DTO: コンストラクタ + プロパティ
- Resource: ChatworkClient 経由で HTTP 実行 + ResponseMapper 呼び出し

実行: `./vendor/bin/pest tests/Feature/Resources/<ResourceName>Test.php`

期待: **全テストが通る**。

### Step 5: Pint で整形

```bash
./vendor/bin/pint src/Resources/<ResourceName>.php src/Data/Requests/* src/Data/Responses/* tests/Feature/Resources/<ResourceName>Test.php
```

### Step 6: PHPStan で静的解析

```bash
./vendor/bin/phpstan analyse src/Resources/<ResourceName>.php src/Data/Requests src/Data/Responses
```

エラーがあれば修正。

### Step 7: Refactor（任意）

- 重複ロジックを抽出（複数 Resource で同じ payload 構築をしているなど）
- 命名の見直し
- 戻り値モード分岐の整理

リファクタ後も全テストが通ることを確認: `composer run ci`

### Step 8: コミット（ユーザー確認）

`.claude/rules/commit-style.md` の規約に従い、以下の粒度でコミット案を提示する:

- `test(resource:<name>): add failing test for <operation>` (Red)
- `feat(resource:<name>): implement <operation>` (Green)
- `refactor(resource:<name>): <理由>` (Refactor、変更がある場合のみ)

**コミットは自動実行しない**。ユーザーが承認した後に手動で `git add` + `git commit`。

## 完了サマリ

```markdown
## TDD Cycle 完了: $operationId

### 生成・変更ファイル
- tests/Feature/Resources/<ResourceName>Test.php
- src/Resources/<ResourceName>.php
- src/Data/Requests/<RequestName>.php
- src/Data/Responses/<ResponseName>.php
- tests/Fixtures/chatwork/<resource>/*.json

### テスト結果
- pest: <pass count> passed
- pint: <files formatted>
- phpstan: 0 errors

### 次の候補
- `openapi-diff-analyzer` で次の未実装エンドポイントを確認
```

## 制約

- ユーザーに無断でコミットしない。
- Red を飛ばして直接実装に入らない。
- Pest テストが通る前に Pint/PHPStan を強制しない（Red 状態は型エラーが残っていても OK）。
