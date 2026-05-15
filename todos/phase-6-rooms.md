# Phase 6: Rooms

## 目的

`RoomsResource` を完成させる。`DELETE /rooms/{room_id}` の `action_type` 分岐（`leave` / `delete`）を 2 つの public method（`leaveRoom` / `deleteRoom`）として明示する。

## 前提

- Phase 5 完了。

## 対象 operation

| operationId | method | path | OpenAPI 備考 |
| --- | --- | --- | --- |
| `listRooms` | GET | `/rooms` | — |
| `createRoom` | POST | `/rooms` | form: `name`, `description?`, `icon_preset?`, `members_admin_ids: csv_integer_list`, `members_member_ids?: csv`, `members_readonly_ids?: csv` |
| `getRoom` | GET | `/rooms/{room_id}` | — |
| `updateRoom` | PUT | `/rooms/{room_id}` | form: `name?`, `description?`, `icon_preset?` |
| `leaveOrDeleteRoom` | DELETE | `/rooms/{room_id}` | query/form: `action_type: enum('leave','delete')` |

公開 API は `leaveOrDeleteRoom` を `leaveRoom()` と `deleteRoom()` に分割する。

## DoD

- 上記 6 公開 method（list / create / find / update / leaveRoom / deleteRoom）が TDD で実装され、各エンドポイントに対し正常系・戻り値モード・エラーケースのテストが緑。

## TODO

### 6-1. IconPreset enum

- [ ] `tests/Unit/Enums/IconPresetTest.php`
  - [Red] `it('exposes all 17 preset values from OpenAPI')`
  - [Red] `it('value() returns kebab-case string')`
- [ ] [Green] `src/Enums/IconPreset.php` — 17 値を確認して enum 化（OpenAPI から取得）

参照: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の `IconPreset` schema

### 6-2. RoomData / CreatedRoom Response DTO

- [ ] `tests/Unit/Data/Responses/RoomDataTest.php`
  - [Red] `it('maps all fields from list-rooms-200 fixture')`
- [ ] [Green] `src/Data/Responses/RoomData.php`（readonly）
- [ ] [Green] `src/Data/Responses/CreatedRoom.php`（readonly: `roomId: int`）

### 6-3. listRooms

- [ ] fixture: `tests/Fixtures/chatwork/rooms/list-rooms-200.json`
- [ ] `tests/Feature/Rooms/ListRoomsTest.php`
  - [Red] `it('GETs /rooms')`
  - [Red] `it('returns Collection<RoomData> in asCollection mode')`
  - [Red] `it('returns array<RoomData> in asDto mode')`
- [ ] [Green] `RoomsResource::list(): mixed`

### 6-4. createRoom

- [ ] fixture: `rooms/create-room-200.json` / `create-room-400.json`
- [ ] `tests/Unit/Data/Requests/CreateRoomRequestTest.php`
  - [Red] `it('requires name and members_admin_ids')`
  - [Red] `it('converts members_admin_ids array to CSV string')` — `[1,2,3]` → `"1,2,3"`
  - [Red] `it('accepts IconPreset enum')`
  - [Red] `it('omits optional fields when null')`
  - [Red] `it('rejects non-integer in members_admin_ids')`
- [ ] [Green] `src/Data/Requests/CreateRoomRequest.php`
- [ ] `tests/Feature/Rooms/CreateRoomTest.php`
  - [Red] `it('POSTs /rooms with form-urlencoded')`
  - [Red] `it('sends members_admin_ids as CSV string')`
  - [Red] `it('returns CreatedRoom DTO')`
- [ ] [Green] `RoomsResource::create(CreateRoomRequest $request): mixed`

### 6-5. getRoom (find)

- [ ] fixture: `rooms/get-room-200.json` / `get-room-404.json`
- [ ] `tests/Feature/Rooms/FindRoomTest.php`
  - [Red] `it('GETs /rooms/{room_id}')`
  - [Red] `it('returns RoomData')`
  - [Red] `it('throws on 404')`
- [ ] [Green] `RoomsResource::find(int $roomId): mixed`

### 6-6. updateRoom

- [ ] fixture: `rooms/update-room-200.json`
- [ ] `tests/Unit/Data/Requests/UpdateRoomRequestTest.php`
  - [Red] `it('all fields optional')`
  - [Red] `it('omits null fields')`
- [ ] [Green] `src/Data/Requests/UpdateRoomRequest.php`
- [ ] `tests/Feature/Rooms/UpdateRoomTest.php`
  - [Red] `it('PUTs /rooms/{room_id}')`
  - [Red] `it('returns UpdatedRoom DTO with room_id')`
- [ ] [Green] `RoomsResource::update(int $roomId, UpdateRoomRequest $request): mixed`

### 6-7. leaveRoom / deleteRoom（action_type 分岐）

- [ ] fixture: `rooms/leave-room-204.json` / `delete-room-204.json`
- [ ] `tests/Feature/Rooms/LeaveRoomTest.php`
  - [Red] `it('DELETEs /rooms/{room_id} with action_type=leave')`
  - [Red] `it('returns 204 / NoContentData')`
- [ ] `tests/Feature/Rooms/DeleteRoomTest.php`
  - [Red] `it('DELETEs /rooms/{room_id} with action_type=delete')`
  - [Red] `it('throws on 403 (no permission)')`
- [ ] [Green] `RoomsResource::leaveRoom(int $roomId): mixed`
- [ ] [Green] `RoomsResource::deleteRoom(int $roomId): mixed`
- [ ] [Green] private `RoomsResource::leaveOrDelete(int $roomId, string $actionType): mixed`

> Chatwork API では `action_type` を query parameter として渡す形と form として渡す形がある。OpenAPI 補完版で確定した方を使う（基本は query）。

### 6-8. RoomsResource → 子 resource への接続

- [ ] `RoomsResource::members(): RoomMembersResource` — Phase 7 で使う（skeleton で OK）
- [ ] `RoomsResource::tasks(): RoomTasksResource` — Phase 8
- [ ] `RoomsResource::files(): RoomFilesResource` — Phase 9
- [ ] `RoomsResource::links(): RoomLinksResource` — Phase 10
- [ ] `RoomsResource::messages(): RoomMessagesResource` — Phase 2 で実装済み

### 6-9. 検証

- [ ] 全テスト緑、`code-reviewer` CRITICAL/HIGH 解消
- [ ] 進捗トラッカー更新
