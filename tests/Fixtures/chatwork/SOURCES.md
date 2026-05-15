# Fixture Sources

各 fixture の値の根拠を記録する。OpenAPI に明示的な `example` がない場合は schema を満たす最小値 + Chatwork 公式 Reference の例に近い形で起こす。

参照:
- 補完済み OpenAPI: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json`
- Chatwork 公式 Reference: https://developer.chatwork.com/reference

## messages/

| fixture | OpenAPI schema | 公式 Reference |
|---|---|---|
| `create-message-201.json` | `MessageId` | https://developer.chatwork.com/reference/post-rooms-room_id-messages |
| `create-message-400.json` | `ErrorResponse` | 同上 |
| `create-message-401.json` | `ErrorResponse` | 同上 |
| `create-message-429.json` | `ErrorResponse` | 同上 |
| `list-messages-200.json` | `Message[]` | https://developer.chatwork.com/reference/get-rooms-room_id-messages |
| `list-messages-204.json` | empty array（204 No Content の body 不在を空配列で代用） | 同上 |
| `get-message-200.json` | `Message` | https://developer.chatwork.com/reference/get-rooms-room_id-messages-message_id |
| `update-message-200.json` | `MessageId` | https://developer.chatwork.com/reference/put-rooms-room_id-messages-message_id |
| `delete-message-200.json` | `MessageId` | https://developer.chatwork.com/reference/delete-rooms-room_id-messages-message_id |
| `mark-read-200.json` | `ReadStatus` | https://developer.chatwork.com/reference/put-rooms-room_id-messages-read |
| `mark-unread-200.json` | `ReadStatus` | https://developer.chatwork.com/reference/put-rooms-room_id-messages-unread |

## oauth/

| fixture | OpenAPI schema | 公式 Reference |
|---|---|---|
| `token-200.json` | `OAuthToken` | https://developer.chatwork.com/docs/oauth |
| `token-400.json` | `OAuthError` | RFC 6749 Section 5.2 |

## 値の起こし方ルール

1. **必須フィールド**は schema が `required` に挙げているもの全てを含める。
2. **オプショナルフィールド**は assertion で参照する可能性があるものは含め、残りは省略する。
3. **文字列フィールド**は短く realistic な値（`"Hello, Chatwork!"`、`"Bob"`、`"sample-access-token"` 等）。秘密情報っぽい値は含めない（`sample-` 接頭辞を付ける）。
4. **数値フィールド**は連番 / Unix timestamp（`2025-01-01T01:00:00 UTC = 1735707600`）。
5. **URL** は `https://example.com/...` を使う。実際の Chatwork URL は使わない。

## 新規 fixture 追加時の手順

1. OpenAPI で対象 operation の response schema を確認（`jq '.paths[...].responses' ...`）。
2. component schema の `required` を満たすキーを必ず入れる。
3. 本ファイル `SOURCES.md` の対応行を追加する。
4. テストで `fixtureJson('resource/operation-status.json')` で読み込んで `Http::response()` に渡す。
