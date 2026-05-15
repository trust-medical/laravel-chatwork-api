# Round1 レビュー: 要件・スコープ完全性

**対象**: `docs/README.md`, `docs/00-overview/project-goals.md`, `docs/01-requirements/*`
**判定者観点**: 実装フェーズへの GO/NO-GO

## Verdict: **GO with caveats**

設計の骨格（戻り値モード、認証、HTTP 形式、Phase 区切り）は CLAUDE.md と整合しており、Phase 2 着手は可能。ただし下記 CRITICAL を着手前に文書化することを強く推奨。

## CRITICAL（着手前に解消）

- **競合差別化が文書化されていない**: `dragon-code/laravel-chatwork-channel` / `sunaoka/chatwork-api-client` 等の既存 OSS との差別化軸（OAuth2 ネイティブ対応・複数 connection・戻り値モード可変等）が `project-goals.md` にも `functional-requirements.md` にも無い。OSS 公開を目標に掲げる以上、明示が必要。
- **エラーモデルが要件層に欠落**: `non-functional-requirements.md:17-22` で「429/5xx は戻り値モードに応じて例外または結果」とあるが、`ChatworkRequestException` / `ChatworkValidationException` の責務分界（送信前 vs 送信後、status code 範囲、payload の保持有無）が要件として未定義。CLAUDE.md には記述があり、要件層へ昇格すべき。
- **`asResponse()` / `asPsrResponse()` / `asResult()` の throw 仕様が要件未記載**: `functional-requirements.md:79-92` は列挙のみ。CLAUDE.md の「throw しない」契約を要件側に明文化しないと、Phase 2 のテスト項目（`tdd-roadmap.md:45-46`）の根拠が宙に浮く。
- **受け入れ基準（Acceptance Criteria）が要件文書側に無い**: テスト項目は `tdd-roadmap.md` に集約されているが、要件 ⇄ テストのトレーサビリティが取れていない。各機能要件に Given/When/Then か AC 番号を付ける必要あり。

## HIGH

- **「全エンドポイント対応」のスコープ宣言が曖昧** (`functional-requirements.md:5`): v2 のどのリビジョン断面か、Webhook・管理API・廃止エンドポイントを含むかが未定義。`02-openapi/normalized-chatwork-api-v2.yaml` を正典とする旨を要件側に明記すべき。
- **「初期対応する Chatwork 記法」のスコープ境界** (`functional-requirements.md:68-77`): `[hr]`「罫線」の表記揺れ、`[piconname:]` / `[picon:]` / `[qt]` 等の未対応理由が不明。`plain()` / `escape()` の挙動定義（記法を文字列化するのか剥がすのか）も未確定。
- **バリデーション境界の根拠** (`functional-requirements.md:99-103`): "1〜65535 文字"・"5MB 上限" の出典が要件側に無い。OpenAPI または公式 Reference の該当箇所への参照を要件に紐付けるべき。
- **多重 connection 切替時の挙動不明** (`functional-requirements.md:27-35`): `withApiToken()` の有効スコープ（1 リクエスト限定 / ビルダー破棄まで）、`connection()` と `withApiToken()` の優先順位、Notification 経由時の解決順が未定義。
- **OAuth2 callback の HTTP 仕様未定義**: route 名前、CSRF 除外、エラー時 redirect、token 保存後の挙動が要件層に無い。`StateStore` / `TokenRepository` の interface 契約も要件で言及無し。

## MEDIUM

- ロギング方針（チャネル名、機微マスキング方式）が `non-functional-requirements.md` に無い。
- timeout のデフォルト値（秒）・connect timeout の有無が未指定 (`non-functional-requirements.md:20`)。
- PHPStan / Pint / CI の必須レベル（level 8 か等）が `tdd-roadmap.md:8-9` で「導入候補」止まり。

## LOW

- `README.md:24` の Laravel 13.x doc URL は将来切れる可能性。バージョン固定 URL 推奨。
- `project-goals.md:6` の「自社専用 / 社内共通 / OSS / Packagist すべて」は対象が広い。リリース戦略（private→public の移行）を一文追記すると親切。

## 強み

- 戻り値モード・認証 2 経路・破壊的 API の命名規則が CLAUDE.md と完全一致しており、要件 → アーキテクチャの一貫性が高い。
- Phase 1〜6 のロードマップが粒度適切で、Phase 2 の AC が `tdd-roadmap.md:35-46` に列挙済み。TDD 着手は即可能。
- `Http::fake()` / `preventStrayRequests()` を非機能要件に明記しており、テスト容易性が後付けでない設計になっている。
