# Phase 8: Room Tasks

## 目的

Room 配下の Task 関連 4 operations を完成させる。`TaskStatus` enum と `limit_type` enum を導入する。

## 前提

- Phase 6 完了（`RoomsResource::tasks()` の skeleton がある）。

## 対象 operation

| operationId | method | path | 備考 |
| --- | --- | --- | --- |
| `listRoomTasks` | GET | `/rooms/{room_id}/tasks` | query: `account_id?: int`, `assigned_by_account_id?: int`, `status?: TaskStatus` |
| `createRoomTask` | POST | `/rooms/{room_id}/tasks` | form: `body`, `to_ids: csv` (required), `limit?: unix`, `limit_type?: enum('none','date','time')` |
| `getRoomTask` | GET | `/rooms/{room_id}/tasks/{task_id}` | — |
| `updateRoomTaskStatus` | PUT | `/rooms/{room_id}/tasks/{task_id}/status` | form: `body: enum('open','done')` |

## DoD

- 4 operations のテスト緑。enum 利用が型安全。

## テスト記述慣行（Phase 0-3 で確立、必須）

詳細は `todos/README.md` の「テスト記述慣行」セクション参照。要点のみ:

- **例外検証** は `try/catch + expect($caught)` で書く。`it()->throws(...)` は Notification/Event 経由で機能しないことがあるため避ける。
- **`Http::fake()`** は `beforeEach` ではなく **各 test 内で** 呼ぶ。stub マージの順序依存で上書きが効かないことがある。`beforeEach` には config 準備だけ書く。
- **fixture 読み込み** は file-scope `fixtureJson('...')`（`tests/Helpers.php`）。`$this->fixtureJson(...)` は PHPStan が解決できない。

## TODO

### 8-1. Enum 定義

- [ ] `tests/Unit/Enums/TaskStatusTest.php`
- [ ] [Green] `src/Enums/TaskStatus.php` — `Open`, `Done`（OpenAPI で確認）
- [ ] `tests/Unit/Enums/TaskLimitTypeTest.php`
- [ ] [Green] `src/Enums/TaskLimitType.php` — `None`, `Date`, `Time`

### 8-2. Response DTOs

- [ ] [Green] `src/Data/Responses/TaskData.php`（task_id, account, assigned_by_account, message_id, body, limit_time, limit_type, status）
- [ ] [Green] `src/Data/Responses/CreatedTask.php`（task_ids: int[]）
- [ ] [Green] `src/Data/Responses/UpdatedTaskStatus.php`（task_id, status）

### 8-3. listRoomTasks

- [ ] fixture: `tasks/list-tasks-200.json`
- [ ] `tests/Feature/RoomTasks/ListTasksTest.php`
  - [Red] `it('GETs /rooms/{room_id}/tasks')`
  - [Red] `it('sends status query as enum value')`
  - [Red] `it('omits null filters')`
  - [Red] `it('returns Collection<TaskData>')`
- [ ] [Green] `src/Resources/RoomTasksResource.php` `list(int $roomId, array $filters = []): mixed`
  - filters: `['account_id' => ?int, 'assigned_by_account_id' => ?int, 'status' => ?TaskStatus]`

### 8-4. createRoomTask

- [ ] fixture: `tasks/create-task-200.json`
- [ ] `tests/Unit/Data/Requests/CreateTaskRequestTest.php`
  - [Red] `it('requires body and to_ids')`
  - [Red] `it('converts to_ids array to CSV')`
  - [Red] `it('accepts TaskLimitType enum')`
  - [Red] `it('converts limit DateTime to unix timestamp')`
  - [Red] `it('rejects body over 65535 chars')`
- [ ] [Green] `src/Data/Requests/CreateTaskRequest.php`
- [ ] `tests/Feature/RoomTasks/CreateTaskTest.php`
  - [Red] `it('POSTs /rooms/{room_id}/tasks')`
  - [Red] `it('returns CreatedTask DTO with task_ids array')`
- [ ] [Green] `RoomTasksResource::create(int $roomId, CreateTaskRequest $request): mixed`

### 8-5. getRoomTask

- [ ] fixture: `tasks/get-task-200.json`
- [ ] `tests/Feature/RoomTasks/FindTaskTest.php`
  - [Red] `it('GETs /rooms/{room_id}/tasks/{task_id}')`
  - [Red] `it('returns TaskData')`
- [ ] [Green] `RoomTasksResource::find(int $roomId, int $taskId): mixed`

### 8-6. updateRoomTaskStatus

- [ ] fixture: `tasks/update-status-200.json`
- [ ] `tests/Feature/RoomTasks/UpdateStatusTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/tasks/{task_id}/status')`
  - [Red] `it('sends body=open / body=done')`
  - [Red] `it('returns UpdatedTaskStatus DTO')`
- [ ] [Green] `RoomTasksResource::updateStatus(int $roomId, int $taskId, TaskStatus $status): mixed`

### 8-7. 検証

- [ ] 全テスト緑、`code-reviewer` 解消、進捗トラッカー更新
