# OpenAPI 仕様の網羅性 / Resource 設計マッピング整合性レビュー

対象資料: `chatwork-api-v2-complemented.openapi.json`（33 operations）、`normalized-chatwork-api-v2.yaml`（32 endpoints）、`docs/04-api-client/resources-and-methods.md`、`docs/06-testing/tdd-roadmap.md`、`CLAUDE.md`

## Verdict

**GO with caveats**

主要 endpoint と Resource クラスの対応はほぼ整合しているが、`me / my / contacts` 配下のメソッド設計と read/unread の必須性などで **未定義参照・パラメータ落ちが複数** 残っている。実装着手前に `resources-and-methods.md` を補強する必要あり。

## 未マッピング operation（OpenAPI には存在するが Resource 設計に無い）

| Operation | Path | 状況 |
|---|---|---|
| `getMe` | `GET /me` | `Chatwork::me()` だけ列挙され `me()->get()` 等のメソッド未定義 |
| `getMyStatus` | `GET /my/status` | `my()` がトップレベル列挙のみ。`status()` メソッド未定義 |
| `listMyTasks` | `GET /my/tasks` | 同上。`my()->tasks($filters)` 等の設計が無い |
| `listContacts` | `GET /contacts` | `Chatwork::contacts()` のみ。`list()` が未定義 |
| `markAsUnread` の必須化 | `PUT /rooms/{room_id}/messages/unread` | Resource では `markAsUnread($roomId, $messageId)` だが、read は `message_id` optional / unread は required という差が反映されていない |
| `issueOAuthToken` | `POST /token` | OpenAPI には在るが `normalized-chatwork-api-v2.yaml` の endpoints リストから欠落（OAuth 章扱いの可能性。明示する必要あり） |

## 未定義参照（Resource 設計にあるが OpenAPI に無い）

| Method | 備考 |
|---|---|
| `Chatwork::rooms()->messages()->deleteMessage()` | OpenAPI 側は `deleteRoomMessage`。命名は OK だが Rooms 側の `deleteRoom()` / `leaveRoom()` と非対称（Messages だけ "delete + Message" にしている）。意図的なら CLAUDE.md に明記すべき |
| `Chatwork::rooms()->messages()->markAsRead($roomId, $messageId)` シグネチャ | OpenAPI では `message_id` は **optional**。設計は引数必須に見えるため `markAsRead($roomId, ?string $messageId = null)` への補足が要 |

## 命名揺れ（CLAUDE.md / docs / OpenAPI）

| 場所 | 表記 | 食い違い |
|---|---|---|
| CLAUDE.md | `replaceMembers($roomId, $request)` | resources-and-methods.md と一致。OpenAPI operationId は `replaceRoomMembers`。問題なし |
| CLAUDE.md | `Chatwork::incomingRequests()->decline($requestId)` | OpenAPI は `declineIncomingRequest` (DELETE)。resources-and-methods.md も同名。OK |
| resources-and-methods.md | `find()` を多用（`rooms()->find()`、`messages()->find()`、`files()->find()`、`tasks()->find()`、`links()->find()`） | OpenAPI は `getXxx`。Laravel 慣習として妥当だが、`me()` / `my()->status()` の取得系を `get()` にするか `find()` にするか **未統一**（設計書に取得系命名規約を 1 行明記すべき） |
| resources-and-methods.md | `deleteLink()`, `deleteRoom()`, `deleteMessage()` | OpenAPI は単一の `delete...`。Rooms の DELETE は `action_type=leave/delete` で 2 メソッドに分割する一方、Links / Messages は単独 DELETE。命名規則自体は CLAUDE.md にあるが「単独 DELETE は `deleteXxx`」「条件分岐 DELETE は `leave/delete` 系で複数メソッド」という二段ルールを明記したほうがよい |
| YAML の `resource: my_tasks` | resources-and-methods.md には `my()->tasks()` 想定だが、YAML では `my_tasks` という別 resource 化されている | Resource クラスを `MyResource` 1 本にするか `MyTasksResource` を分けるか曖昧 |

## パラメータ落ち（enum / array / required の扱いが Resource 設計から欠落）

1. **`members_admin_ids` / `members_member_ids` / `members_readonly_ids`**  
   OpenAPI 上 `type: string`（カンマ区切り）。Resource 設計では `$request` 渡しとしか書かれておらず、**int[] → CSV** 変換責務（Request value object か Resource 層か）が未定義。

2. **`icon_preset` enum (17 値)**  
   `group, check, document, meeting, event, project, business, study, security, star, idea, heart, magcup, beer, music, sports, travel`。`Enums/RoomIconPreset` を作る前提が CLAUDE.md にあるが docs 側にリストが無い。

3. **`action_type` enum (`leave` / `delete`)**  
   Resource では `leaveRoom()` / `deleteRoom()` に分割済み。OK。だが「内部 enum 化するかリテラル直書きか」未定義。

4. **`limit_type` enum (`none` / `date` / `time`)・`TaskStatus` enum (`open` / `done`)**  
   `Enums/` に置く想定だが resources-and-methods.md の `tasks()->updateStatus($roomId, $taskId, $status)` で `$status` が string / enum どちらか不明。

5. **`force`, `self_unread`, `link`, `link_need_acceptance`, `need_acceptance`, `create_download_url`**  
   OpenAPI ではすべて `integer (0/1)`。Resource 設計の `force: true` / `selfUnread()` は **bool** 受け取り。Request builder で bool → 0/1 に変換する責務の所在を明記すべき。

6. **`upload_room_file` の `max_bytes: 5242880` (5MB)**  
   Resource では `upload($roomId, $file, message: '...')` のみ。ファイルサイズ事前 validation の責務が未定義。

7. **`markAsRead` の `message_id` optional / `markAsUnread` の required**  
   Resource シグネチャに差異が反映されていない。

## 強み

- 32 endpoints の path / method / operationId は OpenAPI と normalized YAML で 1 件（`issueOAuthToken`）を除き完全に一致しており、Resource 分類（rooms / room_messages / room_members / room_tasks / room_files / room_links / incoming_requests / me / my / contacts）も OpenAPI tag と整合している。
- `action_type=leave/delete` を `leaveRoom()` / `deleteRoom()` に分けるなど、破壊的操作の明示命名規則は CLAUDE.md に既に文書化されておりレビュー上の論点が少ない。
