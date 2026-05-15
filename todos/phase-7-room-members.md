# Phase 7: Room Members

## 目的

Room メンバーの一覧取得と一括更新を完成させる。CSV integer list 変換ロジックを共通化する。

## 前提

- Phase 6 完了（`RoomsResource::members()` の skeleton がある）。

## 対象 operation

| operationId | method | path | form / query |
| --- | --- | --- | --- |
| `listRoomMembers` | GET | `/rooms/{room_id}/members` | — |
| `replaceRoomMembers` | PUT | `/rooms/{room_id}/members` | `members_admin_ids: csv` (required), `members_member_ids?: csv`, `members_readonly_ids?: csv` |

## DoD

- 2 operations のテスト緑。CSV 変換が Request DTO 層で完結し、Resource は `toArray()` をそのまま `asForm()` に渡すだけ。

## テスト記述慣行（Phase 0-3 で確立、必須）

詳細は `todos/README.md` の「テスト記述慣行」セクション参照。要点のみ:

- **例外検証** は `try/catch + expect($caught)` で書く。`it()->throws(...)` は Notification/Event 経由で機能しないことがあるため避ける。
- **`Http::fake()`** は `beforeEach` ではなく **各 test 内で** 呼ぶ。stub マージの順序依存で上書きが効かないことがある。`beforeEach` には config 準備だけ書く。
- **fixture 読み込み** は file-scope `fixtureJson('...')`（`tests/Helpers.php`）。`$this->fixtureJson(...)` は PHPStan が解決できない。

## TODO

### 7-1. CSV integer list 共通化

- [ ] `tests/Unit/Data/Requests/CsvIntegerListTest.php`
  - [Red] `it('joins int array with comma')`
  - [Red] `it('rejects non-integer elements')`
  - [Red] `it('returns null when input is null')`
- [ ] [Green] `src/Data/Requests/Concerns/CsvIntegerList.php`（trait）または `src/Support/CsvIntegerList.php`（static class）
  - `static toCsv(array $ids): string`
  - `static rejectNonInt(array $ids): void`
- [ ] [Refactor] Phase 6 の `CreateRoomRequest` をこの共通化に移行

### 7-2. MemberData / ReplacedMembers Response DTO

- [ ] `tests/Unit/Data/Responses/MemberDataTest.php`
- [ ] [Green] `src/Data/Responses/MemberData.php`（readonly: account_id, role, name, chatwork_id, organization_id, organization_name, department, avatar_image_url）
- [ ] [Green] `src/Data/Responses/ReplacedMembers.php`（readonly: admin / member / readonly の3配列）

### 7-3. listRoomMembers

- [ ] fixture: `members/list-members-200.json`
- [ ] `tests/Feature/RoomMembers/ListMembersTest.php`
  - [Red] `it('GETs /rooms/{room_id}/members')`
  - [Red] `it('returns Collection<MemberData>')`
- [ ] [Green] `src/Resources/RoomMembersResource.php` `list(int $roomId): mixed`

### 7-4. replaceRoomMembers

- [ ] fixture: `members/replace-members-200.json`
- [ ] `tests/Unit/Data/Requests/ReplaceMembersRequestTest.php`
  - [Red] `it('requires members_admin_ids')`
  - [Red] `it('converts arrays to CSV')`
  - [Red] `it('omits optional CSV when empty')`
- [ ] [Green] `src/Data/Requests/ReplaceMembersRequest.php`
- [ ] `tests/Feature/RoomMembers/ReplaceMembersTest.php`
  - [Red] `it('PUTs /rooms/{room_id}/members with form')`
  - [Red] `it('sends members_admin_ids as CSV string')`
  - [Red] `it('returns ReplacedMembers DTO')`
- [ ] [Green] `RoomMembersResource::replaceMembers(int $roomId, ReplaceMembersRequest $request): mixed`

### 7-5. 検証

- [ ] 全テスト緑
- [ ] `code-reviewer` CRITICAL/HIGH 解消
- [ ] 進捗トラッカー更新
