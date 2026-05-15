# Phase 9: Room Files

## 目的

ファイルアップロード（multipart）を含む 3 operations を完成させる。5MB の事前検証を Request DTO 層で確実に実装する。

## 前提

- Phase 6 完了。

## 対象 operation

| operationId | method | path | 備考 |
| --- | --- | --- | --- |
| `listRoomFiles` | GET | `/rooms/{room_id}/files` | query: `account_id?: int` |
| `uploadRoomFile` | POST | `/rooms/{room_id}/files` | multipart: `file` (required, 5MB上限), `message?` (1-65535) |
| `getRoomFile` | GET | `/rooms/{room_id}/files/{file_id}` | query: `create_download_url?: 0/1` |

## DoD

- 3 operations のテスト緑。multipart の `Http::fake()` 検証が確立。5MB 超過時に HTTP は発生せず `ChatworkValidationException`。

## テスト記述慣行（Phase 0-3 で確立、必須）

詳細は `todos/README.md` の「テスト記述慣行」セクション参照。要点のみ:

- **例外検証** は `try/catch + expect($caught)` で書く。`it()->throws(...)` は Notification/Event 経由で機能しないことがあるため避ける。
- **`Http::fake()`** は `beforeEach` ではなく **各 test 内で** 呼ぶ。stub マージの順序依存で上書きが効かないことがある。`beforeEach` には config 準備だけ書く。
- **fixture 読み込み** は file-scope `fixtureJson('...')`（`tests/Helpers.php`）。`$this->fixtureJson(...)` は PHPStan が解決できない。

## TODO

### 9-1. FileData / UploadedFile DTO

- [ ] [Green] `src/Data/Responses/FileData.php`（file_id, account, message_id, filename, filesize, upload_time, download_url? optional）
- [ ] [Green] `src/Data/Responses/UploadedFile.php`（file_id: int）

### 9-2. listRoomFiles

- [ ] fixture: `files/list-files-200.json`
- [ ] `tests/Feature/RoomFiles/ListFilesTest.php`
  - [Red] `it('GETs /rooms/{room_id}/files')`
  - [Red] `it('sends account_id query when provided')`
  - [Red] `it('omits account_id when null')`
  - [Red] `it('returns Collection<FileData>')`
- [ ] [Green] `src/Resources/RoomFilesResource.php` `list(int $roomId, ?int $accountId = null): mixed`

### 9-3. uploadRoomFile (multipart)

- [ ] fixture: `files/upload-file-200.json`
- [ ] `tests/Unit/Data/Requests/UploadFileRequestTest.php`
  - [Red] `it('rejects file larger than 5MB')`
  - [Red] `it('accepts string path / resource / SplFileInfo / UploadedFile')`
  - [Red] `it('message optional, max 65535 chars')`
  - [Red] `it('exposes asMultipart() returning array<key, mixed> for asAttach()')`
- [ ] [Green] `src/Data/Requests/UploadFileRequest.php`
  - `__construct(public readonly mixed $file, public readonly ?string $message = null)`
  - `validate()` で `filesize()` または stream の長さ取得 → 5MB 超で `ChatworkValidationException`
  - `asMultipart(): array` — `[['name' => 'file', 'contents' => ..., 'filename' => ...], ...]`
- [ ] `tests/Feature/RoomFiles/UploadFileTest.php`
  - [Red] `it('POSTs multipart to /rooms/{room_id}/files')`
  - [Red] `it('attaches file part')` — `Http::assertSent` で `$request->data()` を確認
  - [Red] `it('includes message part when provided')`
  - [Red] `it('throws ChatworkValidationException for >5MB without HTTP call')`
  - [Red] `it('returns UploadedFile DTO')`
- [ ] [Green] `RoomFilesResource::upload(int $roomId, mixed $file, ?string $message = null): mixed`
  - 内部で `Http::attach('file', $contents, $filename)->attach('message', $message)->post($url)` を組み立てる
- [ ] [Refactor] multipart 構築を `ChatworkPendingRequestFactory` に拡張するか、Resource 内に閉じるかを判定

参照: `docs/04-api-client/request-response.md` の Multipart Request

### 9-4. getRoomFile

- [ ] fixture: `files/get-file-200.json`
- [ ] `tests/Feature/RoomFiles/FindFileTest.php`
  - [Red] `it('GETs /rooms/{room_id}/files/{file_id}')`
  - [Red] `it('sends create_download_url=1 when true')`
  - [Red] `it('omits create_download_url when null')`
  - [Red] `it('returns FileData with download_url when requested')`
- [ ] [Green] `RoomFilesResource::find(int $roomId, int $fileId, ?bool $createDownloadUrl = null): mixed`

### 9-5. multipart テストヘルパ

- [ ] `tests/Pest.php` または共通 trait に `assertMultipartHas(string $partName)` ヘルパを追加（後続 fixture でも使えるよう抽象化）

### 9-6. 検証

- [ ] 全テスト緑、`code-reviewer` CRITICAL/HIGH 解消
- [ ] 進捗トラッカー更新
