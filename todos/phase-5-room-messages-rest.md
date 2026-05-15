# Phase 5: Room Messages 残メソッド

## 目的

`RoomMessagesResource` の残 6 operations を完成させる。Phase 2 で確立した実装パターン（Request DTO → Resource method → Response DTO → 戻り値モード）に沿って横展開する。

## 前提

- Phase 2 完了。
- fixture: Phase 0 で `tests/Fixtures/chatwork/messages/` 配下に下記が揃っている前提。

## 対象 operation

| operationId | method | path | OpenAPI 備考 |
| --- | --- | --- | --- |
| `listRoomMessages` | GET | `/rooms/{room_id}/messages` | query: `force?: 0/1` |
| `getRoomMessage` | GET | `/rooms/{room_id}/messages/{message_id}` | — |
| `updateRoomMessage` | PUT | `/rooms/{room_id}/messages/{message_id}` | form: `body` |
| `deleteRoomMessage` | DELETE | `/rooms/{room_id}/messages/{message_id}` | — |
| `markRoomMessagesAsRead` | PUT | `/rooms/{room_id}/messages/read` | form: `message_id?: string`（**optional**） |
| `markRoomMessagesAsUnread` | PUT | `/rooms/{room_id}/messages/unread` | form: `message_id: string`（**required**） |

## DoD

- 6 operations すべて TDD で実装され、各エンドポイントに対し:
  - 正常系のテスト（fixture でレスポンスを fake）
  - 戻り値モード網羅（asArray / asDto / asCollection / asResponse / asPsrResponse / asResult）
  - エラーケース（400 / 404 / 429）
- Pint / PHPStan / Pest 緑
- カバレッジ 80%+

## TODO

### 5-1. listRoomMessages

- [ ] fixture 確認 / 追加: `list-messages-200.json` / `list-messages-204.json`
- [ ] `tests/Feature/RoomMessages/ListMessagesTest.php`
  - [Red] `it('GETs /rooms/{room_id}/messages')`
  - [Red] `it('sends force=1 query when force=true')`
  - [Red] `it('omits force when null')`
  - [Red] `it('returns Collection<MessageData> in asCollection mode')`
  - [Red] `it('returns array of MessageData in asDto mode')`
  - [Red] `it('returns empty result on 204')`
  - [Red] `it('throws ChatworkRequestException on 401')`
- [ ] [Green] `src/Data/Responses/MessageData.php`（readonly DTO）
- [ ] [Green] `RoomMessagesResource::list(int $roomId, ?bool $force = null): mixed`
- [ ] [Refactor]

### 5-2. getRoomMessage (find)

- [ ] fixture: `get-message-200.json`
- [ ] `tests/Feature/RoomMessages/FindMessageTest.php`
  - [Red] `it('GETs /rooms/{room_id}/messages/{message_id}')`
  - [Red] `it('returns MessageData DTO')`
  - [Red] `it('throws on 404')`
- [ ] [Green] `RoomMessagesResource::find(int $roomId, string $messageId): mixed`

### 5-3. updateRoomMessage

- [ ] fixture: `update-message-200.json`
- [ ] `tests/Feature/RoomMessages/UpdateMessageTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/messages/{message_id} with form body')`
  - [Red] `it('rejects empty body')` — `ChatworkValidationException`
  - [Red] `it('rejects body over 65535 chars')`
  - [Red] `it('returns UpdatedMessage DTO')`
- [ ] [Green] `src/Data/Requests/UpdateMessageRequest.php`
- [ ] [Green] `src/Data/Responses/UpdatedMessage.php`
- [ ] [Green] `RoomMessagesResource::update(int $roomId, string $messageId, string $body): mixed`

### 5-4. deleteRoomMessage

- [ ] fixture: `delete-message-200.json`
- [ ] `tests/Feature/RoomMessages/DeleteMessageTest.php`
  - [Red] `it('DELETEs /rooms/{room_id}/messages/{message_id}')`
  - [Red] `it('returns DeletedMessage DTO')`
  - [Red] `it('returns NoContentData on 204')`
- [ ] [Green] `src/Data/Responses/DeletedMessage.php`
- [ ] [Green] `RoomMessagesResource::deleteMessage(int $roomId, string $messageId): mixed`

### 5-5. markRoomMessagesAsRead

- [ ] fixture: `mark-read-200.json`
- [ ] `tests/Feature/RoomMessages/MarkAsReadTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/messages/read')`
  - [Red] `it('omits message_id when null')` — optional
  - [Red] `it('sends message_id when provided')`
  - [Red] `it('returns MarkReadResult DTO with unread_num/mention_num')`
- [ ] [Green] `src/Data/Responses/MarkReadResult.php`
- [ ] [Green] `RoomMessagesResource::markAsRead(int $roomId, ?string $messageId = null): mixed`

### 5-6. markRoomMessagesAsUnread

- [ ] fixture: `mark-unread-200.json`
- [ ] `tests/Feature/RoomMessages/MarkAsUnreadTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/messages/unread')`
  - [Red] `it('requires message_id')` — null/empty で `ChatworkValidationException`
  - [Red] `it('returns MarkUnreadResult DTO')`
- [ ] [Green] `src/Data/Responses/MarkUnreadResult.php`
- [ ] [Green] `RoomMessagesResource::markAsUnread(int $roomId, string $messageId): mixed`

### 5-7. Resource クラスの再整理（Refactor）

- [ ] `RoomMessagesResource` の private helpers（URL 組み立て / payload 整形）を抽出して重複を削減
- [ ] PHPStan の `mixed` 戻り値を `@phpstan-return` で意図を明示
- [ ] DocBlock に operationId / OpenAPI への参照を記載

### 5-8. 検証

- [ ] 全 6 operation の全テスト緑
- [ ] `code-reviewer` agent CRITICAL/HIGH 解消
- [ ] `00-overview.md` の進捗トラッカーを更新

## 完了後

→ Phase 6 以降は独立並行可能。
