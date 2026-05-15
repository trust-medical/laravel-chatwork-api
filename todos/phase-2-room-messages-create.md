# Phase 2: POST /rooms/{room_id}/messages

## 目的

MVP コアエンドポイント `createRoomMessage` を TDD で完成させる。Phase 3 以降のすべての Resource 実装パターンの参照実装とする。

## 前提

- Phase 1 完了。
- fixture: `tests/Fixtures/chatwork/messages/create-message-{201,400,401,429}.json` 配置済み。

## DoD

- Test ID `P2-T01` 〜 `P2-T15` すべて緑（`docs/06-testing/tdd-roadmap.md`）。
- Pint / PHPStan / Pest 緑。
- カバレッジ 80% 以上（src/Resources、src/Data、src/Http、src/ChatworkClient）。

## TODO

### 2-1. CreateMessageRequest DTO

- [ ] `tests/Unit/Data/Requests/CreateMessageRequestTest.php`
  - [Red] `it('accepts body and selfUnread')`
  - [Red] `it('rejects empty body')` — `ChatworkValidationException`
  - [Red] `it('rejects body over 65535 chars')` — 同上
  - [Red] `it('converts selfUnread true to 1')` / `false to 0`
  - [Red] `it('omits selfUnread when not provided')` — payload に key 自体が無い
  - [Red] `it('toArray returns only string|int values')`
- [ ] [Green] `src/Data/Requests/CreateMessageRequest.php`
  - `__construct(public readonly string $body, public readonly ?bool $selfUnread = null)`
  - `toArray(): array{body: string, self_unread?: int}`
  - `validate(): void` — body 文字数、空チェック
- [ ] [Refactor] バリデーション規則を private const に集約

参照: `docs/04-api-client/request-response.md` の パラメータエンコード規約、`docs/01-requirements/functional-requirements.md` の Chatwork記法スコープ境界

### 2-2. CreatedMessage Response DTO

- [ ] `tests/Unit/Data/Responses/CreatedMessageTest.php`
  - [Red] `it('maps message_id from array')` — fixture `create-message-201.json` から
  - [Red] `it('is readonly')` — `toBeReadonly` カスタム expectation
- [ ] [Green] `src/Data/Responses/CreatedMessage.php`
  - `readonly class CreatedMessage { public function __construct(public string $messageId) {} }`
  - `static fromArray(array $data): self` — `new self((string) $data['message_id'])`

参照: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `createRoomMessage` レスポンススキーマ

### 2-3. RoomMessagesResource::create()（P2-T01〜T09）

- [ ] `tests/Feature/RoomMessages/CreateMessageTest.php`
  - [Red] `P2-T01: it('POSTs to /rooms/{room_id}/messages')`
  - [Red] `P2-T02: it('sends application/x-www-form-urlencoded')`
  - [Red] `P2-T03: it('sends x-chatworktoken header for api_token connection')`
  - [Red] `P2-T04: it('sends Authorization Bearer for bearer connection')`
  - [Red] `P2-T05: it('includes body in payload')`
  - [Red] `P2-T06: it('sends self_unread=1 when selfUnread is true')`
  - [Red] `P2-T07: it('omits self_unread when not specified')` — または OpenAPI に合わせて 0 送信
  - [Red] `P2-T08: it('throws ChatworkValidationException for empty body')` — HTTP は発生しない
  - [Red] `P2-T09: it('throws ChatworkValidationException for body over 65535 chars')`
- [ ] [Green] `src/Resources/RoomsResource::messages(): RoomMessagesResource`
- [ ] [Green] `src/Resources/RoomMessagesResource::create(int $roomId, string $body, ?bool $selfUnread = null): mixed`
  - `CreateMessageRequest` 生成 → `validate()` → `ChatworkClient` 経由で POST → `ResponseMapper` で変換
- [ ] [Green] `src/ChatworkClient::send(string $method, string $path, array $payload, ResponseMode $mode, ?string $dtoClass): mixed`
  - `PendingRequestFactory::create($connection)->asForm()->{$method}($path, $payload)` 実行
  - `ResponseMapper::map(...)` 呼び出し
- [ ] [Refactor] `RoomMessagesResource` の URL 組み立てを private method に
- [ ] [Refactor] `ChatworkClient::send()` の signature を整理（後続 Resource で再利用されるため）

### 2-4. 戻り値モード網羅（P2-T10〜T15）

- [ ] `tests/Feature/RoomMessages/CreateMessageResponseModeTest.php`
  - [Red] `P2-T10: it('returns CreatedMessage DTO on 201 in asDto mode')` — fixture `create-message-201.json`
  - [Red] `P2-T11: it('throws ChatworkRequestException with errors() on 400')`
  - [Red] `P2-T12: it('returns rateLimit array on 429')`
  - [Red] `P2-T13: it('does not throw in asResponse mode')` — 400 でも Illuminate Response が返る
  - [Red] `P2-T14: it('returns failed Result in asResult mode on 4xx')` — `$result->failed()` true
  - [Red] `P2-T15: it('returns array in asArray mode')`
- [ ] [Green] 必要なら `ResponseMapper` を補強（Phase 1 で skeleton 実装済み）
- [ ] [Refactor] テストの fixture 読み込みを `beforeEach` で共通化

### 2-5. 簡易メソッド（ChatworkClient::createRoomMessage）

- [ ] `tests/Feature/ChatworkClient/CreateRoomMessageShorthandTest.php`
  - [Red] `it('createRoomMessage delegates to RoomMessagesResource::create')` — `Http::assertSent` で同一 URL
- [ ] [Green] `ChatworkClient::createRoomMessage(int $roomId, string $body, ?bool $selfUnread = null): mixed`
  - 内部で `$this->rooms()->messages()->create(...)` を呼ぶだけ

参照: `docs/04-api-client/resources-and-methods.md`

### 2-6. テスト品質チェック

- [ ] `Http::preventStrayRequests()` が `setUp` で呼ばれていることを確認
- [ ] 各テストが `Http::fake([...])` で必要な URL のみ fake していること
- [ ] エラー fixture（400 / 401 / 429）の `errors[]` / `error` フィールドが現実的な値であること
- [ ] PHPStan で型エラーなし、特に `mixed` の戻り値を utilizing 箇所で安全にダウンキャストできていること

### 2-7. 検証

- [ ] `P2-T01` 〜 `P2-T15` 全部緑
- [ ] `code-reviewer` agent CRITICAL/HIGH 解消
- [ ] commit 粒度: Red/Green/Refactor を分ける（最低 5〜7 コミット想定）

## 完了後

Phase 3 / Phase 4 / Phase 5 は並行着手可能。リソースの空きがあれば 3 並行で進める。
