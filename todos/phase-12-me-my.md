# Phase 12: Me / My

## 目的

`GET /me`, `GET /my/status`, `GET /my/tasks` の 3 operations を完成させる。

## 前提

- Phase 5 完了（並行可能）。

## 対象 operation

| operationId | method | path | 備考 |
| --- | --- | --- | --- |
| `getMe` | GET | `/me` | — |
| `getMyStatus` | GET | `/my/status` | — |
| `listMyTasks` | GET | `/my/tasks` | query: `assigned_by_account_id?: int`, `status?: TaskStatus` |

## DoD

- 3 operations のテスト緑。`Chatwork::me()`, `Chatwork::my()->status()`, `Chatwork::my()->tasks()` がそれぞれ動作。

## TODO

### 12-1. Response DTOs

- [ ] [Green] `src/Data/Responses/MeData.php`（account_id, room_id, name, chatwork_id, organization_id, organization_name, department, title, url, introduction, mail, tel_organization, tel_extension, tel_mobile, skype, facebook, twitter, avatar_image_url, login_mail）
- [ ] [Green] `src/Data/Responses/MyStatusData.php`（unread_room_num, unread_num, mention_num, mytask_room_num, mytask_num）
- [ ] [Green] `src/Data/Responses/MyTaskData.php`（task_id, room（room_id/name/icon_path）, assigned_by_account, message_id, body, limit_time, limit_type, status）

### 12-2. getMe

- [ ] fixture: `me/get-me-200.json`
- [ ] `tests/Feature/Me/GetMeTest.php`
  - [Red] `it('GETs /me')`
  - [Red] `it('returns MeData DTO')`
- [ ] [Green] `src/Resources/MeResource.php` `get(): mixed`
- [ ] [Green] `ChatworkManager::me(): MeResource`

### 12-3. getMyStatus

- [ ] fixture: `my/get-status-200.json`
- [ ] `tests/Feature/My/GetStatusTest.php`
  - [Red] `it('GETs /my/status')`
  - [Red] `it('returns MyStatusData DTO')`
- [ ] [Green] `src/Resources/MyResource.php` `status(): mixed`

### 12-4. listMyTasks

- [ ] fixture: `my/list-tasks-200.json`
- [ ] `tests/Feature/My/ListTasksTest.php`
  - [Red] `it('GETs /my/tasks')`
  - [Red] `it('sends assigned_by_account_id and status query when provided')`
  - [Red] `it('omits null filters')`
  - [Red] `it('returns Collection<MyTaskData>')`
- [ ] [Green] `MyResource::tasks(array $filters = []): mixed`
- [ ] [Green] `ChatworkManager::my(): MyResource`

### 12-5. 検証

- [ ] 全テスト緑、`code-reviewer` 解消、進捗トラッカー更新
