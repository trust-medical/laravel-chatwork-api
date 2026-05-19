# Resource API設計

## 基本方針

Facade、client、resource chainのどこから呼んでも同じrequest objectとresponse mapperを通す。
同じAPIに複数の入口を用意しても、実装本体は重複させない。

## 命名規則

OpenAPIの`operationId` と Resource methodの対応規則。

| OpenAPI operationId | Resource method | 備考 |
| --- | --- | --- |
| `list_*` | `list($filters = [])` | 一覧取得 |
| `create_*` | `create($request)` | 新規作成 |
| `get_*_by_id` | `find($id)` | 単一取得 |
| `update_*` | `update($id, $request)` | 更新 |
| `delete_*`（曖昧） | `leaveRoom` / `deleteRoom` / `decline` 等の明示名 | `action_type` で動作分岐するものは個別メソッド |
| `mark_read` / `mark_unread` | `markAsRead` / `markAsUnread` | 状態変更系 |

トップレベル取得（`/me`, `/my/*`, `/contacts`）も `me() / my()->status() / my()->tasks() / contacts()` のように接頭辞を統一する。

## トップレベル

```php
Chatwork::me();
Chatwork::my();
Chatwork::contacts();
Chatwork::rooms();
Chatwork::incomingRequests();
```

### Me

`GET /me`

```php
Chatwork::me()->get(); // operationId: get_me
```

戻り値は `MeData` DTO。

### My

`GET /my/status` / `GET /my/tasks`

```php
Chatwork::my()->status();                 // operationId: get_my_status
Chatwork::my()->tasks($filters = []);     // operationId: list_my_tasks
```

`tasks()` のフィルタは `assigned_by_account_id?: int` / `status?: TaskStatus` を受ける。

### Contacts

`GET /contacts`

```php
Chatwork::contacts()->list(); // operationId: list_contacts
```

## Messages

初期TDD対象。

```php
Chatwork::rooms()->messages()->create($roomId, '本文');
Chatwork::rooms()->messages()->list($roomId, force: true);
Chatwork::rooms()->messages()->find($roomId, $messageId);
Chatwork::rooms()->messages()->update($roomId, $messageId, '本文');
Chatwork::rooms()->messages()->deleteMessage($roomId, $messageId);
Chatwork::rooms()->messages()->markAsRead($roomId, $messageId = null);
Chatwork::rooms()->messages()->markAsUnread($roomId, $messageId);
```

OpenAPI 上の差分:

- `markAsRead` (`PUT /rooms/{room_id}/messages/read`) の `message_id` は **optional**。指定なしならその時点までの全件を既読にする。
- `markAsUnread` (`PUT /rooms/{room_id}/messages/unread`) の `message_id` は **required**。

メッセージ投稿:

```php
Chatwork::rooms()->messages()->create($roomId, '本文');
```

## Rooms

```php
Chatwork::rooms()->list();
Chatwork::rooms()->create($request);
Chatwork::rooms()->find($roomId);
Chatwork::rooms()->update($roomId, $request);
Chatwork::rooms()->leaveRoom($roomId);
Chatwork::rooms()->deleteRoom($roomId);
```

`DELETE /rooms/{room_id}` は `action_type` により退席と削除が分かれるため、公開APIでは `leaveRoom()` と `deleteRoom()` に分ける。

## Members

```php
Chatwork::rooms()->members()->list($roomId);
Chatwork::rooms()->members()->replaceMembers($roomId, $request);
```

`PUT /rooms/{room_id}/members` は一括変更のため、破壊的操作として明示名にする。

## Tasks

```php
Chatwork::rooms()->tasks()->list($roomId, $filters);
Chatwork::rooms()->tasks()->create($roomId, $request);
Chatwork::rooms()->tasks()->find($roomId, $taskId);
Chatwork::rooms()->tasks()->updateStatus($roomId, $taskId, $status);
```

## Files

```php
Chatwork::rooms()->files()->list($roomId, accountId: 123);
Chatwork::rooms()->files()->upload($roomId, $file, message: '本文');
Chatwork::rooms()->files()->find($roomId, $fileId, createDownloadUrl: true);
```

## Invitation Links

```php
Chatwork::rooms()->links()->find($roomId);
Chatwork::rooms()->links()->create($roomId, $request);
Chatwork::rooms()->links()->update($roomId, $request);
Chatwork::rooms()->links()->deleteLink($roomId);
```

## Incoming Requests

```php
Chatwork::incomingRequests()->list();
Chatwork::incomingRequests()->accept($requestId);
Chatwork::incomingRequests()->decline($requestId);
```

`decline()` は `DELETE /incoming_requests/{request_id}` の明示名とする。

## パラメータ検証の責務帰属

OpenAPI の制約（enum / array / required / maximum）に対する責務分割。

| 制約 | 責務 | 失敗時 |
| --- | --- | --- |
| `members_admin_ids` 等 CSV integer list | Request DTO で `implode(',', …)` + 要素の型検証 | `ChatworkValidationException` |
| `icon_preset`（17値 enum）| `IconPreset` enum で受ける | type error or `ChatworkValidationException` |
| `limit_type` / `TaskStatus` 等 enum | 対応PHP enum を引数として受ける | type error |
| `force` / `self_unread` / `create_download_url` 等 bool | Request DTO で `0` / `1` に変換 | — |
| `file` 5MB 上限 | Request DTO で `filesize()` 検証 | `ChatworkValidationException` |
| message / task body 65535 文字 | Request DTO で `mb_strlen()` 検証 | `ChatworkValidationException` |
| `room_id` 等の必須 path parameter | Resource method の引数型で強制 | type error |

詳細なエンコード規約は `docs/04-api-client/request-response.md` を参照。

