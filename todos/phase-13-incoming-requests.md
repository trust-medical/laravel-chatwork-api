# Phase 13: Incoming Requests

## 目的

コンタクト承認依頼系の 3 operations を完成させる。`DELETE /incoming_requests/{request_id}` は `decline()` の明示名にする。

## 前提

- Phase 5 完了（並行可能）。

## 対象 operation

| operationId | method | path |
| --- | --- | --- |
| `listIncomingRequests` | GET | `/incoming_requests` |
| `acceptIncomingRequest` | PUT | `/incoming_requests/{request_id}` |
| `declineIncomingRequest` | DELETE | `/incoming_requests/{request_id}` |

## DoD

- 3 operations のテスト緑。`Chatwork::incomingRequests()` 経由で動作。

## TODO

### 13-1. DTOs

- [ ] [Green] `src/Data/Responses/IncomingRequestData.php`（request_id, account_id, message, name, chatwork_id, organization_id, organization_name, department, avatar_image_url）
- [ ] [Green] `src/Data/Responses/AcceptedRequest.php`（同上 + 受理時の応答）
- [ ] [Green] `src/Data/Responses/DeclinedRequest.php`（request_id）

### 13-2. listIncomingRequests

- [ ] fixture: `incoming-requests/list-200.json`
- [ ] `tests/Feature/IncomingRequests/ListTest.php`
  - [Red] `it('GETs /incoming_requests')`
  - [Red] `it('returns Collection<IncomingRequestData>')`
- [ ] [Green] `src/Resources/IncomingRequestsResource.php` `list(): mixed`
- [ ] [Green] `ChatworkManager::incomingRequests(): IncomingRequestsResource`

### 13-3. acceptIncomingRequest

- [ ] fixture: `incoming-requests/accept-200.json`
- [ ] `tests/Feature/IncomingRequests/AcceptTest.php`
  - [Red] `it('PUTs /incoming_requests/{request_id}')`
  - [Red] `it('returns AcceptedRequest DTO')`
  - [Red] `it('throws on 404')`
- [ ] [Green] `IncomingRequestsResource::accept(int $requestId): mixed`

### 13-4. declineIncomingRequest

- [ ] fixture: `incoming-requests/decline-200.json`
- [ ] `tests/Feature/IncomingRequests/DeclineTest.php`
  - [Red] `it('DELETEs /incoming_requests/{request_id}')`
  - [Red] `it('returns DeclinedRequest DTO or NoContentData')`
- [ ] [Green] `IncomingRequestsResource::decline(int $requestId): mixed`

### 13-5. 検証

- [ ] 全テスト緑、`code-reviewer` 解消、進捗トラッカー更新
- [ ] **全 32 OpenAPI operations 実装完了**を確認（`docs/02-openapi/chatwork-api-v2-complemented.openapi.json` の paths 全件 vs Resource methods）
