# 実装フェーズ移行可否レビュー（Round 1 総合判定）

- 対象: `trust-medical/laravel-chatwork-api`（PHP ^8.3 / Laravel ^11-^13、Chatwork API v2 用 Composer パッケージ）
- レビュー範囲: `docs/` 全 16 ファイル + `CLAUDE.md` + `composer.json` / `phpunit.xml` / `.claude/rules/testing.md` 等の補助
- 日付: 2026-05-15
- 形式: 6 観点を並列 SubAgent でレビューし、本 summary に集約

## 総合 Verdict: **GO with caveats**

設計の骨格（依存方向・戻り値モード・認証 2 経路・破壊操作命名・テスト戦略）は実装着手に十分な水準。**ただし Phase 2 (`POST /rooms/{room_id}/messages`) を Red から書き始める前に解消すべき CRITICAL が複数ある**ため、まず Phase 0 (= docs 微修正 + Phase 1 のスケルトン確定) を 0.5〜1 日程度差し込んでから Phase 2 に入ることを推奨する。

| 観点 | 担当レポート | Verdict | CRITICAL | HIGH |
|---|---|---|---|---|
| 1. Requirements / Scope | `01_requirements.md` | GO with caveats | 4 | 5 |
| 2. Architecture / DI | `02_architecture.md` | GO with caveats | 0 | 5 |
| 3. API Client / Auth / HTTP | `03_api_client.md` | GO with caveats | 5 | 6 |
| 4. Notifications | `04_notifications.md` | GO with caveats | 2 | 3 |
| 5. Testing / TDD Roadmap | `05_testing.md` | GO with caveats | 2 | 3 |
| 6. OpenAPI Coverage | `06_openapi_coverage.md` | GO with caveats | 0 | 5 |

CRITICAL 合計 **13 件**、HIGH 合計 **27 件**。CRITICAL のうち約半数は要件・設計ドキュメントへの追記のみで閉じ、実装方針の根本変更は不要。

---

## 実装着手前に必ず閉じる項目（Phase 0 ブロッカー）

### A. fixture と Phase 1 DoD（テスト着手の物理的ブロッカー）
- `tests/Fixtures/chatwork/` が `.gitkeep` のみで実体なし（`05_testing.md` CRITICAL #1）。
- ファイル命名規則 `create-message-200.json` と Chatwork 実 API の `201` レスポンスの整合が取れていない。OpenAPI 側を正として `…-201.json` に統一するか、status code を file 名に入れない方針に切り替える決定が必要。
- Phase 1 の Definition of Done（ServiceProvider 起動・Facade 解決・Connection 解決・auth header 付与の各テストが green になる条件）が tdd-roadmap.md に箇条書きしかない（`05_testing.md` CRITICAL #2）。

### B. 認証 / HTTP 契約の細部（実装と同時に決められない部分）
- `csv_integer_list`（`members_admin_ids` 等）を `implode(',', …)` する責務層が未定義。`asForm()` に array をそのまま渡すと `key[0]=…` 形式になり 400 で返る（`03_api_client.md` CRITICAL #1）。Request DTO 層で string 化するのが妥当だが、docs での確定が必要。
- `x-chatworktoken` ヘッダと `Authorization: Bearer` の **排他保証層**（どのクラスが両方つかないことを保証するか）が未明示（`03_api_client.md` CRITICAL #2）。
- Chatwork のエラーボディが `ErrorResponse { errors: string[] }` と OAuth の `{ error, error_description }` の二系統あるのに `ChatworkRequestException` の getter 設計が片寄っている（`03_api_client.md` CRITICAL #3）。
- `ChatworkValidationException` の発火条件（どのバリデーション規則がパッケージ側 / どこが Chatwork 側か）が CLAUDE.md にしか書かれていない（`03_api_client.md` CRITICAL #4）。
- 429 の `x-ratelimit-*` ヘッダにアクセスする公開 API が未定義（`03_api_client.md` CRITICAL #5）。

### C. 要件 ⇄ 実装のトレーサビリティ
- 受け入れ基準が `tdd-roadmap.md` に集約され、`functional-requirements.md` から逆引きできない（`01_requirements.md` CRITICAL #4）。
- `asResponse() / asPsrResponse() / asResult()` の "throw しない" 契約が要件層に存在しない（`01_requirements.md` CRITICAL #3）。
- 競合（`dragon-code/laravel-chatwork-channel` 等）との差別化が project-goals に記載なし（`01_requirements.md` CRITICAL #1）。
- エラーモデルが CLAUDE.md のみで要件層に昇格していない（`01_requirements.md` CRITICAL #2）。

### D. Notification の契約
- `toChatwork($notifiable)` の戻り値型（`ChatworkMessage` 厳格 vs `string` 糖衣）が未確定（`04_notifications.md` CRITICAL #1）。
- `ChatworkNotification` クラスと `ChatworkMessage` クラスの責務分離が不明瞭（`04_notifications.md` CRITICAL #2）。

---

## 実装着手後でも段階的に閉じられる項目（HIGH 抜粋）

- Resource 設計と OpenAPI の整合（`getMe`, `getMyStatus`, `listMyTasks`, `listContacts` の Resource メソッド未定義 / `MyResource` の package-structure.md 上の欠落）
- `TokenProvider` interface のシグネチャ確定（Phase 1 中盤、OAuth Phase 4 着手前まで）
- `ConnectionFactory` の存在の有無（Manager 実装時に決定）
- `markAsRead` の `message_id` optional / `markAsUnread` の required 差分
- `csv_integer_list` だけでなく `icon_preset` 17 値 enum、`limit_type` enum、bool → 0/1 変換、5MB ファイル検証の責務帰属
- HTTP Client ログ経由のトークン漏洩対策（log middleware の header redact）
- queue 失敗時の再送ポリシー（4xx fail-fast / 5xx・429 は queue retry に委譲）
- 配列 route の部分失敗の扱い（MVP では fail-fast or 後送り）

---

## 推奨ロードマップ調整

### Phase 0（着手前、推定 0.5〜1 日）— **docs only**

1. `docs/01-requirements/functional-requirements.md`
   - エラーモデル節（`ChatworkRequestException` / `ChatworkValidationException` / `error` vs `errors`）を追加
   - 受け入れ基準セクション（Phase ごとに「これが緑なら完了」の条件）を追加
   - `asResponse / asPsrResponse / asResult` の throw しない契約を要件として明文化
   - 競合差別化（公式 Reference + OpenAPI 駆動 + 戻り値 6 モード + OAuth2 + multi-connection）を 00-overview/project-goals.md に追記

2. `docs/02-openapi/source-policy.md` + `docs/04-api-client/request-response.md`
   - `csv_integer_list` を Request DTO 内で string 化する責務帰属を明記
   - `x-chatworktoken` と Bearer の排他は `Credentials` 実装の責任に閉じることを明記

3. `docs/03-package-architecture/package-structure.md`
   - `MyResource` を追加
   - `ConnectionFactory` を採用するかしないかを決定し記述
   - `TokenProvider` interface のシグネチャ案を載せる

4. `docs/05-notifications/notification-channel.md`
   - `toChatwork()` の戻り値型を明文化
   - `ChatworkNotification` / `ChatworkMessage` の役割の差を 1 段落で書く

5. `docs/06-testing/tdd-roadmap.md`
   - Phase 1 DoD を test ID レベル（`ChatworkServiceProvider boots`, `Facade resolves manager`, `Connection injects x-chatworktoken header` 等）に固定
   - fixture 命名規則を確定（`create-message-201.json` 推奨）

6. `tests/Fixtures/chatwork/messages/` に Phase 2 用 fixture を OpenAPI example から物理生成（`create-message-201.json`, `create-message-400.json`）

### Phase 1（実装着手）

- ServiceProvider / Facade / `Connection` / `Credentials` の 2 実装 / `ChatworkPendingRequestFactory` を TDD で骨組み着手
- `TokenProvider` interface は **interface だけ** Phase 1 で確定（実装は Phase 4 で）

### Phase 2（既定: `POST /rooms/{room_id}/messages`）

- Phase 0 で fixture と DoD が閉じれば、Phase 2 は当初計画どおりに開始可

---

## 強み（既に十分整っている点）

- `Http::preventStrayRequests()` 必須・OpenAPI を fixture 出典とする一貫した方針（テスト戦略の品質は業界標準超）
- 戻り値 6 モードと例外モデルの責任分離が明快
- 破壊操作の命名規則（`leaveRoom` / `deleteRoom` / `replaceMembers` / `decline`）が docs と CLAUDE.md で一貫
- OAuth2 を `Auth/OAuth/` に隔離 + callback route をデフォルト無効化 = OSS パッケージとして堅実なセキュリティ姿勢
- 補完済み OpenAPI と正規化 yaml の二段構成、`source-policy.md` で出典規律が明文化されている

---

## 次アクション

| # | アクション | 担当 | 工数目安 |
|---|---|---|---|
| 1 | Phase 0 docs パッチ群（A〜D の CRITICAL を docs だけで閉じる） | docs author | 0.5〜1 日 |
| 2 | `tests/Fixtures/chatwork/messages/create-message-{201,400}.json` 生成 | docs/test author | 0.5 時間 |
| 3 | Phase 1 着手（Red 開始: `ChatworkServiceProvider boot` テスト） | impl | 1〜2 日 |
| 4 | Phase 2 着手（Red 開始: `RoomMessagesResource::create()`） | impl | 1〜2 日 |

各観点の詳細は同階層の `01_requirements.md` 〜 `06_openapi_coverage.md` を参照。
