# API クライアント層・認証層レビュー (round1)

## Verdict

**GO with caveats** — 認証/HTTP/Resource の骨格と OpenAPI 整備は実装着手に足る水準。ただし HTTP 契約とエラーモデルに実装前に詰めるべき詳細が残る。Phase 1 (ServiceProvider/Facade/Connection) は即着手可、Phase 2 (`POST /rooms/{room_id}/messages`) 着手前に CRITICAL を解消する。

## CRITICAL — 実装前に必ず詰める

1. **`csv_integer_list` のエンコード規約が未定義**
   `normalized-chatwork-api-v2.yaml:86–88, 152–154, 276` で `members_admin_ids` / `to_ids` 等を `csv_integer_list` と定義しているが、`request-response.md` および `authentication.md` に「カンマ区切りで1キー1値として送る」明文がない。Laravel `asForm()` に array を渡すと `key[0]=…&key[1]=…` 形式になり Chatwork は 400 を返す。`Data/Requests/*` で必ず `implode(',', …)` する責務をどの層が持つか (`Request` value object か `ChatworkClient` か) を確定する。

2. **`x-chatworktoken` と `Authorization: Bearer` の排他保証ポイントが未明示**
   `authentication.md:11–39` で「両方は送らない」と書かれているが、`withApiToken()` と `withBearerToken()` のチェーンや TokenProvider 差替えで両ヘッダが同時に乗らない保証層 (`ChatworkPendingRequestFactory` か `Connection`) と、不整合時の挙動 (上書き / 例外) が決まっていない。CLAUDE.md の制約を満たすには `Credentials` を `oneof` enum 的に扱う旨を明記する必要がある。

3. **エラーモデルが二系統あるのに `ChatworkRequestException` のフィールドが未設計**
   通常 API は `ErrorResponse { errors: string[] }` (openapi.json:1879–1896)、OAuth token endpoint は `OAuthError { error, error_description, error_uri }` (openapi.json:2910–2928) と body 形が異なる。`request-response.md:95–98` は `errors` だけに触れ、OAuth 系の `error_description` のマッピングが落ちている。例外プロパティとして `errors: array`, `oauthError: ?string`, `oauthErrorDescription: ?string`, `status: int`, `requestId: ?string` を一通り保持するかを決める。

4. **`ChatworkValidationException` の発火条件が曖昧**
   CLAUDE.md は「送信前バリデーション失敗は戻り値モードに関わらず `ChatworkValidationException`」と明言するが、`request-response.md` / `authentication.md` のどこにも「どのフィールドで」「どの Request DTO が」検証するかの記述がない。`Data/Requests/*` ctor で投げるのか、Resource メソッドで前段検証するのかを設計書に追記する。

5. **429 のレートリミットヘッダ取り扱いが未定義**
   openapi.json:1849–1867 で `x-ratelimit-limit/remaining/reset` が定義されているのに、`request-response.md:99–102` は「retry しない」のみで、これらを `ChatworkRequestException` / `ChatworkResult` から取り出せる API が無い。`->rateLimit(): RateLimitInfo` を例外と Result に持たせるか決める (実装容易だが規約として今書く)。

## HIGH — 設計詰め切りが必要

- **HTTP Client ログのトークン漏洩経路** (`authentication.md:11–39`, CLAUDE.md セキュリティ章): Laravel HTTP Client は `Http::macro` や `withMiddleware` 経由でリクエスト/ヘッダがログに出る可能性がある。`ChatworkPendingRequestFactory` で `withOptions(['debug' => false])` 強制、`__toString()`/`__debugInfo()` で `Credentials` を伏字化、例外 `getMessage()` に token を含めない、までを文書化する。
- **OAuth refresh フローのタイミング** (`authentication.md:77–86`): `TokenProvider::credentials()` がどの段階で refresh を試みるか (call 毎 / 401 受信時 / `expires_in` を見て事前)、また race condition 時の lock 方針が無い。少なくとも「`TokenRepository::find` + 期限判定 + refresh + `save`」のシーケンスと、refresh 失敗時の例外 (`ChatworkRequestException` か新規 `OAuthTokenException` か) を決める。
- **OAuth callback のエラーハンドリング** (`authentication.md:88–106`): `?error=access_denied` で戻ってきた場合、`state` 検証失敗の場合、`TokenRepository` 未設定の場合の HTTP レスポンス (リダイレクト / view / JSON) が未定義。controller の責務に「例外を投げる + 既定 handler でレンダ」など方針を書く。
- **`PendingRequest` の `connect/timeout` デフォルト** (`request-response.md` 全体): タイムアウト未指定だと Guzzle 既定 (無制限) になり、Notification チャネルから呼んだ際にキューワーカーを長時間ブロックする。`config('chatwork.http.timeout')` の既定値 (例 10s) を明示する。
- **`PUT /rooms/{room_id}/messages/read|unread` の引数が string 型**(`normalized-chatwork-api-v2.yaml:195, 208`): `resources-and-methods.md:28–29` の `markAsRead($roomId, $messageId)` は単一 message_id を取るシグネチャだが、API 仕様としては `message_id` は string でカンマ区切りも許容する可能性がある。Reference を再確認し、`markAsRead($roomId, string|array $messageIds)` への拡張余地を残すか決める。
- **`POST /rooms/{room_id}/files` の content-length 事前検証** (`request-response.md:58–62`, `normalized-chatwork-api-v2.yaml:331`): 5MB を `ChatworkValidationException` でローカル弾きするか、サーバ 400 に任せるかが未定義。stream 入力時は size 取得不可なので「path / SplFileInfo は事前検証、stream は非検証」のような条件分岐を書く。

## MEDIUM / LOW

- `resources-and-methods.md:83` `deleteLink($roomId)` と CLAUDE.md「破壊操作は明示名」は整合するが、`docs/02-openapi/normalized-chatwork-api-v2.yaml:392` の operationId は `delete_room_link`。docs 間で「メソッド名 ↔ operationId」対応表を 1 つ持つと監査が楽。
- `request-response.md:71` `create_download_url=1` の例で「null は送らない」と「`false→0` 変換」が両立するかが曖昧。bool→int 変換のルールを `Data/Requests/*` 規約に明記。
- `authentication.md:64–68` `TokenProvider::credentials()` の戻り値が `Credentials` 1 種類のみで、`scope` / `expires_at` を返す経路がない。OAuth 検証側で必要になるので `TokenSet` を返す上位 interface も検討。
- `resources-and-methods.md:14` の `Chatwork::incomingRequests()` と CLAUDE.md `src/Resources/IncomingRequestsResource.php` の命名は一致。OK。
- LOW: `request-response.md:21` の `Accept: application/json` は OK だが `User-Agent: laravel-chatwork-api/{version}` の付与方針も書いておくと運用観点で有用。
- LOW: `authentication.md:46–50` `Connection::make(name: ...)` の named args 例が `Connection.php` ファクトリ仕様としては `from(array)` / `make(...)` のどちらが正かまだ揺れる。1 つに決める。
- LOW: OpenAPI に `parameters: roomId/messageId/...` の `$ref` が共通化されている (openapi.json:1559–1604)。`Data/Requests/*` の path 引数型もこれを正にする旨をどこかに書くと整合管理しやすい。

## 強み

- API Token / OAuth2 の両モードを `Credentials` / `TokenProvider` / `TokenRepository` / `StateStore` の 4 interface で綺麗に分離しており、Phase 4 への拡張余地が広い。
- 補完済み OpenAPI (`chatwork-api-v2-complemented.openapi.json`) と `normalized-chatwork-api-v2.yaml` の役割分担が `source-policy.md` で明文化されており、Resource 設計と仕様ソースの追跡が容易。
- 破壊操作の命名 (`leaveRoom`/`deleteRoom`/`replaceMembers`/`decline`/`deleteLink`) が `resources-and-methods.md` と CLAUDE.md で一貫しており、誤操作リスクが低い。
