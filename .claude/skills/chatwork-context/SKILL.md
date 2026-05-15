---
name: chatwork-context
description: src/ と tests/ 編集時に自動ロードされる軽量な Chatwork API ドメイン用語集。URL 規約・action_type 命名規則・記法表記・主要エンドポイント分類など。詳細は .claude/rules/ に委譲する。
user-invocable: false
paths: src/**, tests/**
---

# Chatwork API ドメイン用語集

Chatwork API v2 を扱う上で必要な最低限の前提を整理する。詳細規約は `.claude/rules/architecture.md` 等を参照。

## エンドポイント分類（src/Resources/）

| Resource | path prefix | 主な operation |
|---|---|---|
| `MeResource` | `/me` | 自分の情報取得 |
| `MyResource` | `/my/*` | 自分のタスク / ステータス |
| `ContactsResource` | `/contacts` | コンタクト一覧 |
| `RoomsResource` | `/rooms`, `/rooms/{room_id}` | ルーム CRUD（list / find / create / update / leaveRoom / deleteRoom） |
| `RoomMessagesResource` | `/rooms/{room_id}/messages` | メッセージ送受信 / read / unread |
| `RoomMembersResource` | `/rooms/{room_id}/members` | メンバー replace（破壊的） |
| `RoomTasksResource` | `/rooms/{room_id}/tasks` | タスク CRUD / updateStatus |
| `RoomFilesResource` | `/rooms/{room_id}/files` | ファイル list / upload / find |
| `RoomLinksResource` | `/rooms/{room_id}/link` | 招待リンク CRUD / deleteLink |
| `IncomingRequestsResource` | `/incoming_requests` | コンタクト依頼 list / accept / decline |

## URL 規約

- Base URI: `https://api.chatwork.com/v2`（config で変更可能）
- path parameter は Resource メソッド引数で受け取り、組み立て前に空値・型を検証
- query parameter は null 値を送らない（`?key=null` 禁止）

## 認証

- API Token: `x-chatworktoken: {token}` ヘッダー
- OAuth2 Bearer: `Authorization: Bearer {token}` ヘッダー
- 同一リクエストで両方を送らない

## HTTP 形式

- POST / PUT（通常）: `application/x-www-form-urlencoded`（`asForm()`）
- POST `/rooms/{room_id}/files`: multipart
- GET: query parameter
- 2025-07-03 以降: POST/PUT は query parameter で受け付けない。body 必須

## 破壊的操作の命名

| API | メソッド名 |
|---|---|
| `DELETE /rooms/{room_id}` (`action_type=leave`) | `leaveRoom()` |
| `DELETE /rooms/{room_id}` (`action_type=delete`) | `deleteRoom()` |
| `PUT /rooms/{room_id}/members` | `replaceMembers()` |
| `DELETE /rooms/{room_id}/messages/{message_id}` | `deleteMessage()` |
| `DELETE /rooms/{room_id}/link` | `deleteLink()` |
| `DELETE /incoming_requests/{request_id}` | `decline()` |

## Chatwork 記法

- `[To:{account_id}]` — 宛先
- `[info]...[/info]` — 情報枠
- `[title]...[/title]` — タイトル（info 内 or 単独）
- `[code]...[/code]` — コードブロック
- 罫線（hr）

本文はデフォルトでそのまま送信する。`plain()` / `escape()` で記法を無効化。

## バリデーション制約

- message body / task body: 1〜65535 文字
- file upload: 5MB 上限
- enum 値は OpenAPI の許可値に従う

## 詳細

- 依存方向・層の責務: `.claude/rules/architecture.md`
- 命名・DTO 規約: `.claude/rules/coding-style.md`
- テスト・fixture: `.claude/rules/testing.md`
- 公式仕様: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json`
