# Phase 11: Contacts

## 目的

`GET /contacts` 1 operation を完成させる。

## 前提

- Phase 5 完了（並行可能）。

## 対象 operation

| operationId | method | path |
| --- | --- | --- |
| `listContacts` | GET | `/contacts` |

## DoD

- 1 operation のテスト緑。

## TODO

### 11-1. ContactData DTO

- [ ] [Green] `src/Data/Responses/ContactData.php`（readonly: account_id, room_id, name, chatwork_id, organization_id, organization_name, department, avatar_image_url）

### 11-2. listContacts

- [ ] fixture: `contacts/list-contacts-200.json`
- [ ] `tests/Feature/Contacts/ListContactsTest.php`
  - [Red] `it('GETs /contacts')`
  - [Red] `it('returns Collection<ContactData>')`
  - [Red] `it('returns array<ContactData> in asDto mode')`
  - [Red] `it('throws ChatworkRequestException on 401')`
- [ ] [Green] `src/Resources/ContactsResource.php` `list(): mixed`
- [ ] [Green] `ChatworkManager::contacts(): ContactsResource`

### 11-3. 検証

- [ ] 全テスト緑、`code-reviewer` 解消、進捗トラッカー更新
