# テスト戦略 / TDD ロードマップ レビュー（Round 1）

## Verdict

**GO with caveats**

土台（Testbench TestCase、`Http::preventStrayRequests()`、`fixture()` / `fixtureJson()` ヘルパ、`toBeReadonly` expect 拡張、PHPStan level 6、`pest --coverage --min=80`）はすでに整備済みで、Phase 1 の終了直後に Phase 2 を Red から開始できる水準にある。ただし Phase 2 の Red を書き始める前に「fixture body の確定」「ステータスコード（201 vs 200）の合意」「Phase 1 完了の DoD 明文化」を解消する必要がある。

## CRITICAL（Phase 2 Red 前に解消）

1. **`messages/create-message-200.json` の中身が未確定**
   `tests/Fixtures/chatwork/` は `.gitkeep` のみで、OpenAPI の response example から起こした fixture が 1 件も無い。`.claude/rules/testing.md` の例では `Http::response($this->fixtureJson(...), 201)` を呼んでいるのに対し、`docs/06-testing/http-fake-strategy.md` の基本形は `200`、ファイル名規則は `create-message-200.json`。**201 を返すなら `create-message-201.json` に揃える**、もしくは Chatwork 公式 Reference に従い 200 に統一する判断を、Red を書く前に確定させる必要がある（OpenAPI: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` を引いて status code を一意に決める）。
2. **Phase 1 の DoD（Definition of Done）が test 列挙のみで未形式化**
   `tdd-roadmap.md` の Phase 1 は箇条書き 4 件のみで、「Facade 経由で `withApiToken()` チェーンが `PendingRequest` にヘッダーを積む」ことを検証する具体テストファイル名／配置が無い。Phase 2 着手の前提となる `ChatworkManager`・`Connection`・`withApiToken/withBearerToken` の API シグネチャは Phase 1 の Green で初めて固まるため、**Phase 1 完了の判定基準（テストファイル一覧）を tdd-roadmap.md に列挙**しないと Phase 2 の Red が「未定義 API への参照」で書けない。

## HIGH

3. **Resource interface と Request DTO の skeleton が未着手**
   `src/` は `.gitkeep` のみ。Phase 2 Red を書くには最低限 `Chatwork::rooms()->messages()->create(int $roomId, string $body)` のシグネチャと、`CreatedMessage` readonly DTO のクラス名／namespace が固定されている必要がある（テストが存在しないクラス名を文字列で参照することになるため、Pint/PHPStan 前にクラスの「空 stub」を作るかどうかの方針を決める）。
4. **`asResponse()` / `asResult()` の戻り値モード切替テスト方針**
   tdd-roadmap.md は項目を列挙するが、`Http::fake()` の戻り値型が `Illuminate\Http\Client\Response` であるのに対し `asResponse()` は Laravel HTTP Response を返す仕様。**変換層（`Http/ResponseMapper`）の境界テストを Phase 2 で書くか Phase 1 末尾で書くか**が未明示。
5. **65535 文字超過テストの根拠ソース**
   `body` 65535 文字制限は OpenAPI / 公式 Reference のどちらから引いた値か明示が無い。Phase 2 Red の境界値テスト（65535 OK / 65536 NG）を書く前に出典をコミットメッセージに残せる状態にする。

## MEDIUM / LOW

6. **MEDIUM**: `Http::fake()` の URL マッチで query string や connection 切替（`Chatwork::connection('sales')` 経由）を区別する例が `http-fake-strategy.md` に無い。Phase 3 までに sequence fake / URL wildcard の選び方ガイドを追加すべき。
7. **MEDIUM**: multipart 検証（Phase 6: files）について「実ファイルではなくテスト用 stream」とあるのみ。`Http::assertSent` で multipart body を検査する具体テクニックが未記載。
8. **LOW**: pre-commit に Pint / PHPStan を入れる予定が `.claude/rules/commit-style.md` に記述されているが、`composer.json` の `scripts.ci` には含まれているものの **git hook 自体が未配置**。Phase 2 中の早い段階で `composer ci` を CI（GitHub Actions）に固定するチケットを切ること。
9. **LOW**: `phpunit.xml` で `CHATWORK_API_TOKEN=testing-token` を渡しているが、Phase 1 で `withApiToken()` チェーンが優先される設計なら env 経由の token がテストに混入しないことを TestCase 側で明示的に潰すアサーションを 1 件入れたい。

## Phase 2 Day-1 Readiness チェックリスト

- [x] `tests/TestCase.php` に `Http::preventStrayRequests()`・`fixture()`／`fixtureJson()` が実装済み — **done**
- [x] `tests/Pest.php` で `uses(TestCase::class)->in('Feature','Unit')` 自動適用済み — **done**
- [ ] `tests/Fixtures/chatwork/messages/create-message-{200|201}.json` が OpenAPI example から作成済み — **not-done**
- [ ] `src/Resources/RoomMessagesResource::create()` と `Data/Responses/CreatedMessage` の class/namespace を tdd-roadmap.md に明記 — **not-done**
- [ ] Phase 1 完了 DoD（テストファイル名リスト）が tdd-roadmap.md に列挙され、Phase 2 Red の前提クラスが固定されている — **not-done**

## 強み

`Http::preventStrayRequests()` を TestCase で強制し、`fixture()`／`fixtureJson()` を実装済み・`toBeReadonly` 拡張・`pest --coverage --min=80`・PHPStan level 6・`.claude/rules/testing.md` のテストファイル配置規約まで揃っており、**「Phase 1 から Red を書き始められる足場」としては業界標準を超える完成度**。あとは fixture 実体と Phase 1 DoD さえ閉じれば走り出せる。
