# TODO リスト

`trust-medical/laravel-chatwork-api` を完成させるための、フェーズごとの実装 TODO リスト。

## 読み方

- 各フェーズは独立した Markdown ファイル（`phase-N-*.md`）。
- TODO はチェックボックス `- [ ]` 形式。完了したら `- [x]` に変更する。
- すべての作業は TDD で進める（Red → Green → Refactor）。Red を飛ばして実装しない。
- 参照すべき docs は各タスクで明示する（`docs/...md` の相対パス）。
- Phase 内のタスクは原則として上から順に着手する。並行できる場合は `[parallel]` タグを付ける。

## ステータス記号

| 記号 | 意味 |
| --- | --- |
| `- [ ]` | 未着手 |
| `- [~]` | 着手中 |
| `- [x]` | 完了 |
| `- [-]` | スキップ（理由を併記） |

## 全体構成

```
todos/
  README.md                          ← このファイル
  00-overview.md                     ← 全フェーズ依存図と進捗トラッカー
  phase-0-setup.md                   ← 開発環境セットアップ、fixture、skeleton
  phase-1-foundation.md              ← ServiceProvider / Manager / Connection / 認証
  phase-2-room-messages-create.md    ← POST /rooms/{room_id}/messages
  phase-3-notifications.md           ← Notification Channel / Message / Route
  phase-4-oauth.md                   ← OAuth2 全フロー
  phase-5-room-messages-rest.md      ← list / find / update / delete / read / unread
  phase-6-rooms.md                   ← /rooms
  phase-7-room-members.md            ← /rooms/{room_id}/members
  phase-8-room-tasks.md              ← /rooms/{room_id}/tasks
  phase-9-room-files.md              ← /rooms/{room_id}/files
  phase-10-room-links.md             ← /rooms/{room_id}/link
  phase-11-contacts.md               ← /contacts
  phase-12-me-my.md                  ← /me, /my/status, /my/tasks
  phase-13-incoming-requests.md      ← /incoming_requests
  phase-14-release.md                ← README / CHANGELOG / CI / Packagist 公開
```

## 共通の TDD サイクル

各エンドポイント実装は次の順で進める。

1. **Fixture 用意**: OpenAPI example から `tests/Fixtures/chatwork/{resource}/{op-kebab}-{status}.json` を生成（Phase 0 で全件生成済みが理想）。
2. **Request DTO の Red**: 入力検証（必須、enum、文字数、ファイルサイズ、CSV変換）のテストを書く。
3. **Request DTO の Green**: 検証ロジックを実装。
4. **Resource method の Red**: `Chatwork::resource()->method(...)` が正しい URL/method/header/body を送ることを `Http::fake()` で検証。
5. **Resource method の Green**: 実装。
6. **Response DTO の Red**: `asDto()` で readonly DTO が返ることを検証。
7. **Response DTO の Green**: DTO 実装。
8. **戻り値モード網羅**: `asArray() / asCollection() / asResponse() / asPsrResponse() / asResult()` の各テスト。
9. **エラーケース**: 400 / 401 / 403 / 404 / 429 / 5xx の各テスト。`ChatworkRequestException` の `errors()` / `rateLimit()` 等の getter を検証。
10. **Refactor**: 重複削除、命名、型注釈の整理。Pint + PHPStan を緑にする。

## コミット粒度

1 つのエンドポイント実装で原則 3 コミットに分割（`.claude/rules/commit-style.md` 参照）。

```
test(resource:messages): add failing test for createRoomMessage
feat(resource:messages): implement createRoomMessage
refactor(resource:messages): extract request builder
```

## 参照ドキュメント

| 内容 | 場所 |
| --- | --- |
| パッケージ概要 | `CLAUDE.md` |
| 機能要件・受入基準 | `docs/01-requirements/functional-requirements.md` |
| 仕様ソース方針 | `docs/02-openapi/source-policy.md` |
| パッケージ構造・依存方向 | `docs/03-package-architecture/package-structure.md` |
| Service Container | `docs/03-package-architecture/service-container.md` |
| 戻り値戦略 | `docs/03-package-architecture/response-strategy.md` |
| 認証 | `docs/04-api-client/authentication.md` |
| HTTP / Request / Response | `docs/04-api-client/request-response.md` |
| Resource API 命名規則 | `docs/04-api-client/resources-and-methods.md` |
| Notification Channel | `docs/05-notifications/notification-channel.md` |
| Message Builder | `docs/05-notifications/chatwork-message-builder.md` |
| Routing | `docs/05-notifications/routing.md` |
| Http::fake() / Fixture | `docs/06-testing/http-fake-strategy.md` |
| TDD ロードマップ | `docs/06-testing/tdd-roadmap.md` |
| 補完済み OpenAPI | `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` |
| 正規化 YAML | `docs/02-openapi/normalized-chatwork-api-v2.yaml` |

## レビュー記録

実装フェーズ移行可否レビューは `review/round1/` にある。Phase 0 の TODO はここで指摘された CRITICAL/HIGH を docs 反映済みである前提で構成されている。
