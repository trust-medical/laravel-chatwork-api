# 全体ロードマップと進捗

## Phase 依存図

```
Phase 0: Setup & Fixture
    ↓
Phase 1: Foundation (ServiceProvider / Manager / Connection / Credentials / PendingRequestFactory)
    ↓
Phase 2: POST /rooms/{room_id}/messages ← MVP コア
    ↓
   ├─→ Phase 3: Notification Channel
   │       (ChatworkChannel / Message / Route)
   │
   ├─→ Phase 4: OAuth2
   │       (OAuthClient / TokenSet / TokenRepository / StateStore / Callback)
   │
   └─→ Phase 5: Message Resource 残
           (list / find / update / delete / markAsRead / markAsUnread)
    ↓
Phase 6: Rooms
Phase 7: Room Members
Phase 8: Room Tasks
Phase 9: Room Files (multipart)
Phase 10: Room Invitation Links
Phase 11: Contacts
Phase 12: Me / My
Phase 13: Incoming Requests
    ↓
Phase 14: Release Preparation
    (README / CHANGELOG / CI / Packagist)
```

### 並行可能な範囲

- Phase 3 / Phase 4 / Phase 5 は Phase 2 完了後に並行着手可能。
- Phase 6〜13 は Phase 5 完了後に独立並行可能（依存なし）。
- Phase 14 はすべての Phase 完了後。

## 進捗トラッカー

| Phase | 名称 | 対象 endpoint 数 | 状態 |
| --- | --- | --- | --- |
| 0 | Setup & Fixture | — | `[x]` |
| 1 | Foundation | — | `[x]` |
| 2 | createRoomMessage | 1 | `[x]` |
| 3 | Notifications | — | `[x]` |
| 4 | OAuth2 | 1 (`/token`) | `[x]` |
| 5 | Messages 残 | 6 | `[ ]` |
| 6 | Rooms | 5 (action_type で2分割) | `[ ]` |
| 7 | Room Members | 2 | `[ ]` |
| 8 | Room Tasks | 4 | `[ ]` |
| 9 | Room Files | 3 | `[ ]` |
| 10 | Room Links | 4 | `[ ]` |
| 11 | Contacts | 1 | `[ ]` |
| 12 | Me / My | 3 | `[ ]` |
| 13 | Incoming Requests | 3 | `[ ]` |
| 14 | Release | — | `[ ]` |

**OpenAPI 全 operations**: 32（うち `leaveOrDeleteRoom` は `action_type` で `leaveRoom` / `deleteRoom` の 2 公開メソッドに分離）。

## OpenAPI operations → Phase 対応表

| operationId | method | path | Phase |
| --- | --- | --- | --- |
| `createRoomMessage` | POST | /rooms/{room_id}/messages | 2 |
| `listRoomMessages` | GET | /rooms/{room_id}/messages | 5 |
| `getRoomMessage` | GET | /rooms/{room_id}/messages/{message_id} | 5 |
| `updateRoomMessage` | PUT | /rooms/{room_id}/messages/{message_id} | 5 |
| `deleteRoomMessage` | DELETE | /rooms/{room_id}/messages/{message_id} | 5 |
| `markRoomMessagesAsRead` | PUT | /rooms/{room_id}/messages/read | 5 |
| `markRoomMessagesAsUnread` | PUT | /rooms/{room_id}/messages/unread | 5 |
| `issueOAuthToken` | POST | /token | 4 |
| `listRooms` | GET | /rooms | 6 |
| `createRoom` | POST | /rooms | 6 |
| `getRoom` | GET | /rooms/{room_id} | 6 |
| `updateRoom` | PUT | /rooms/{room_id} | 6 |
| `leaveOrDeleteRoom` | DELETE | /rooms/{room_id} | 6 |
| `listRoomMembers` | GET | /rooms/{room_id}/members | 7 |
| `replaceRoomMembers` | PUT | /rooms/{room_id}/members | 7 |
| `listRoomTasks` | GET | /rooms/{room_id}/tasks | 8 |
| `createRoomTask` | POST | /rooms/{room_id}/tasks | 8 |
| `getRoomTask` | GET | /rooms/{room_id}/tasks/{task_id} | 8 |
| `updateRoomTaskStatus` | PUT | /rooms/{room_id}/tasks/{task_id}/status | 8 |
| `listRoomFiles` | GET | /rooms/{room_id}/files | 9 |
| `uploadRoomFile` | POST | /rooms/{room_id}/files | 9 |
| `getRoomFile` | GET | /rooms/{room_id}/files/{file_id} | 9 |
| `getRoomLink` | GET | /rooms/{room_id}/link | 10 |
| `createRoomLink` | POST | /rooms/{room_id}/link | 10 |
| `updateRoomLink` | PUT | /rooms/{room_id}/link | 10 |
| `deleteRoomLink` | DELETE | /rooms/{room_id}/link | 10 |
| `listContacts` | GET | /contacts | 11 |
| `getMe` | GET | /me | 12 |
| `getMyStatus` | GET | /my/status | 12 |
| `listMyTasks` | GET | /my/tasks | 12 |
| `listIncomingRequests` | GET | /incoming_requests | 13 |
| `acceptIncomingRequest` | PUT | /incoming_requests/{request_id} | 13 |
| `declineIncomingRequest` | DELETE | /incoming_requests/{request_id} | 13 |

## 工数見積（参考）

| Phase | 工数目安（1人想定） | 備考 |
| --- | --- | --- |
| 0 | 0.5〜1 日 | 環境セットアップと fixture 一括生成 |
| 1 | 1〜2 日 | 11 test ID（P1-T01〜T11） |
| 2 | 1〜2 日 | 15 test ID（P2-T01〜T15） |
| 3 | 1.5 日 | Channel + 配列route + 衝突検知 |
| 4 | 2〜3 日 | 認可URL + callback + refresh + lock + state |
| 5 | 1.5 日 | 6 operations |
| 6 | 1.5 日 | 5 operations + action_type 分岐 |
| 7 | 1 日 | CSV変換ロジックの実装が含まれる |
| 8 | 1.5 日 | enum 多め |
| 9 | 1.5 日 | multipart + 5MB検証 |
| 10 | 1 日 | 4 operations |
| 11 | 0.5 日 | 1 operation |
| 12 | 1 日 | 3 operations |
| 13 | 1 日 | 3 operations |
| 14 | 1〜2 日 | README / CI / 公開準備 |
| **合計** | **約 18〜23 日** | TDD で進める前提、休憩や調査時間を除く |

## 不変条件（実装中に常に保つ）

- `Http::preventStrayRequests()` が `tests/TestCase.php` の setUp で必ず呼ばれる。
- 1 リクエストで `x-chatworktoken` と `Authorization: Bearer` が同時に乗らない。`Credentials` 実装で構造的に保証。
- `ChatworkValidationException` は HTTP 通信前に投げる。
- `ChatworkRequestException` は `asArray / asDto / asCollection` のみで投げる。`asResponse / asPsrResponse / asResult` は throw しない。
- API token / client_secret / refresh_token は例外メッセージ・ログ・debug 情報に出ない。
- `Notification` channel 経由は `asResult()` 固定。4xx fail-fast、5xx/429/network は queue retry に委譲。
- 戻り値モードは Manager の immutable clone で表現する。グローバル状態を変更しない。
- すべての TDD は Red → Green → Refactor の順。Red をスキップしない。
- すべての Phase は完了時に Pint + PHPStan + Pest がローカルで緑であること。

## レビューポイント

各 Phase 完了時に `code-reviewer` agent を起動し、CRITICAL/HIGH を解消してから次 Phase に進む。`security-reviewer` agent は Phase 4（OAuth2）完了時に必須。
