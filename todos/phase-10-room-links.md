# Phase 10: Room Invitation Links

## 目的

Room の招待リンク CRUD（4 operations）を完成させる。`DELETE /rooms/{room_id}/link` は曖昧な短名を避けて `deleteLink()` とする。

## 前提

- Phase 6 完了。

## 対象 operation

| operationId | method | path | 備考 |
| --- | --- | --- | --- |
| `getRoomLink` | GET | `/rooms/{room_id}/link` | — |
| `createRoomLink` | POST | `/rooms/{room_id}/link` | form: `code?`, `description?`, `need_acceptance?: 0/1` |
| `updateRoomLink` | PUT | `/rooms/{room_id}/link` | form: 同上（すべて optional） |
| `deleteRoomLink` | DELETE | `/rooms/{room_id}/link` | — |

## DoD

- 4 operations のテスト緑。

## テスト記述慣行（Phase 0-3 で確立、必須）

詳細は `todos/README.md` の「テスト記述慣行」セクション参照。要点のみ:

- **例外検証** は `try/catch + expect($caught)` で書く。`it()->throws(...)` は Notification/Event 経由で機能しないことがあるため避ける。
- **`Http::fake()`** は `beforeEach` ではなく **各 test 内で** 呼ぶ。stub マージの順序依存で上書きが効かないことがある。`beforeEach` には config 準備だけ書く。
- **fixture 読み込み** は file-scope `fixtureJson('...')`（`tests/Helpers.php`）。`$this->fixtureJson(...)` は PHPStan が解決できない。

## TODO

### 10-1. LinkData DTO

- [ ] [Green] `src/Data/Responses/LinkData.php`（public: bool, url: string, need_acceptance: bool, description: string）
- [ ] [Green] `src/Data/Responses/DeletedLink.php`（public: bool）

### 10-2. getRoomLink (find)

- [ ] fixture: `links/get-link-200.json` / `get-link-404.json`
- [ ] `tests/Feature/RoomLinks/FindLinkTest.php`
  - [Red] `it('GETs /rooms/{room_id}/link')`
  - [Red] `it('returns LinkData')`
  - [Red] `it('throws on 404 when link not created')`
- [ ] [Green] `src/Resources/RoomLinksResource.php` `find(int $roomId): mixed`

### 10-3. createRoomLink

- [ ] fixture: `links/create-link-200.json`
- [ ] `tests/Unit/Data/Requests/CreateLinkRequestTest.php`
  - [Red] `it('all fields optional')`
  - [Red] `it('converts need_acceptance bool to 0/1')`
- [ ] [Green] `src/Data/Requests/CreateLinkRequest.php`
- [ ] `tests/Feature/RoomLinks/CreateLinkTest.php`
  - [Red] `it('POSTs /rooms/{room_id}/link')`
  - [Red] `it('returns LinkData')`
- [ ] [Green] `RoomLinksResource::create(int $roomId, CreateLinkRequest $request): mixed`

### 10-4. updateRoomLink

- [ ] fixture: `links/update-link-200.json`
- [ ] `tests/Unit/Data/Requests/UpdateLinkRequestTest.php`
- [ ] [Green] `src/Data/Requests/UpdateLinkRequest.php`
- [ ] `tests/Feature/RoomLinks/UpdateLinkTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/link')`
  - [Red] `it('returns LinkData')`
- [ ] [Green] `RoomLinksResource::update(int $roomId, UpdateLinkRequest $request): mixed`

### 10-5. deleteRoomLink

- [ ] fixture: `links/delete-link-200.json`
- [ ] `tests/Feature/RoomLinks/DeleteLinkTest.php`
  - [Red] `it('DELETEs /rooms/{room_id}/link')`
  - [Red] `it('returns DeletedLink DTO')`
- [ ] [Green] `RoomLinksResource::deleteLink(int $roomId): mixed`

### 10-6. 検証

- [ ] 全テスト緑、`code-reviewer` 解消、進捗トラッカー更新
