# TDDロードマップ

## テストスタック

- Pest
- Orchestra Testbench
- Laravel HTTP Client `Http::fake()`
- PHPStanまたはLarastanは後続で導入検討
- Laravel Pintは導入候補

## 原則

- 実APIは叩かない。
- 最初に失敗するテストを書く。
- 1エンドポイントずつ、request、response、例外、Notification連携を小さく実装する。
- fixtureはChatwork公式ReferenceとローカルOpenAPI JSONを参照して作る。

## Phase 1: パッケージ土台

Phase 2 が前提とするクラス・interface・シグネチャを Phase 1 で固定する。Phase 2 着手時点で「`Chatwork::rooms()->messages()->create()` がコンパイル通る」状態を作るため、skeleton（throw new \LogicException('not implemented') の本体）を含めて Green を取る。

### Test ID とそれぞれの Definition of Done

| Test ID | 検証内容 | 期待結果 |
| --- | --- | --- |
| `P1-T01` | `ChatworkServiceProvider` が register/boot で例外を投げない | Testbench で `$app->register(ChatworkServiceProvider::class)` が成功 |
| `P1-T02` | `ChatworkServiceProvider` が `config/chatwork.php` を merge する | `config('chatwork.base_uri')` が default URI を返す |
| `P1-T03` | Facade `Chatwork::` が `ChatworkManager` を解決する | `Chatwork::getFacadeRoot() instanceof ChatworkManager` |
| `P1-T04` | default connection を config から解決する | `Chatwork::connection()` が `default` を解決し例外を投げない |
| `P1-T05` | `Chatwork::connection('sales')` が config の sales 接続を解決する | `Connection::name() === 'sales'` |
| `P1-T06` | `Chatwork::forConnection(Connection $c)` が値オブジェクトを直接受ける | Manager が `$c` を保持 |
| `P1-T07` | `Chatwork::withApiToken('TOKEN')` で `x-chatworktoken: TOKEN` ヘッダーが送信される | `Http::assertSent` で確認（dummy GET先 = base_uri） |
| `P1-T08` | `Chatwork::withBearerToken('TOKEN')` で `Authorization: Bearer TOKEN` ヘッダーが送信される | 同上 |
| `P1-T09` | API Token と Bearer が同じリクエストに両方乗らない | 上記2テストで他方が absent |
| `P1-T10` | `ChatworkPendingRequestFactory` が `User-Agent` と `Accept: application/json` を付与する | `Http::assertSent` |
| `P1-T11` | `Chatwork::asResult()` 等が immutable clone を返す | `Chatwork::asResult() !== Chatwork::getFacadeRoot()` で確認、default mode が変化しない |

### Phase 1 完了条件（DoD）

- 上記 `P1-T01` 〜 `P1-T11` がすべて Green
- `tests/Feature/` 配下に各 test が1ファイルずつ存在
- `Http::preventStrayRequests()` が `tests/TestCase.php` の setUp で呼ばれている
- `composer test` で全 test が成功
- `phpstan` level 6 でエラーなし（src/ のみ対象）
- 以下のクラス skeleton が `src/` に存在し、Phase 2 で実装される method が `LogicException` を投げるだけの状態でも OK:
  - `ChatworkServiceProvider`, `ChatworkManager`, `ChatworkClient`, `Connection`, `Facades\Chatwork`
  - `Auth\Credentials`, `Auth\ApiTokenCredentials`, `Auth\BearerTokenCredentials`, `Auth\TokenProvider`
  - `Http\ChatworkPendingRequestFactory`, `Http\ResponseMapper`
  - `Resources\RoomsResource`, `Resources\RoomMessagesResource`（method shell のみ）
  - `Data\Requests\CreateMessageRequest`（Phase 2 で本実装）
  - `Exceptions\ChatworkRequestException`, `Exceptions\ChatworkValidationException`, `Exceptions\ChatworkAuthenticationException`

## Phase 2: 初期エンドポイント

対象:

```text
POST /rooms/{room_id}/messages
```

### Test ID

| Test ID | 検証内容 |
| --- | --- |
| `P2-T01` | `Chatwork::rooms()->messages()->create($roomId, $body)` が `POST /rooms/{room_id}/messages` へリクエストする |
| `P2-T02` | `application/x-www-form-urlencoded` で送信される |
| `P2-T03` | API Token connection で `x-chatworktoken` ヘッダーが送信される |
| `P2-T04` | Bearer Token connection で `Authorization: Bearer` ヘッダーが送信される |
| `P2-T05` | `body` が payload に含まれる |
| `P2-T06` | `selfUnread()` 指定時に `self_unread=1` が送信される |
| `P2-T07` | `selfUnread()` 未指定時に `self_unread=0` が送信される（または送信されない、OpenAPI に合わせる） |
| `P2-T08` | 空 body で `ChatworkValidationException`（送信されない） |
| `P2-T09` | 65535文字超過 body で `ChatworkValidationException`（送信されない） |
| `P2-T10` | 201成功時に `CreatedMessage` DTO を返す（fixture: `create-message-201.json`） |
| `P2-T11` | 400時に `ChatworkRequestException`、`$e->errors()` が parsed errors を返す |
| `P2-T12` | 429時に `ChatworkRequestException`、`$e->rateLimit()` が `x-ratelimit-*` を返す |
| `P2-T13` | `asResponse()` は throw せず Laravel Response を返す |
| `P2-T14` | `asResult()` は throw せず 成功/失敗 Result を返す |
| `P2-T15` | `asArray()` で配列を返す |

## Phase 3: Notification

対象:

- `ChatworkChannel`
- `ChatworkMessage`
- `ChatworkNotification`
- `ChatworkRoute`

テスト項目:

- `$user->notify($notification)`（`ChatworkNotification` 経由）でPOSTされる。
- `Notification::route('chatwork', $roomId)` でPOSTされる。
- `ChatworkRoute::room($roomId)->connection('sales')` が該当connectionを使う。
- `routeNotificationForChatwork()` のroom IDが使われる。
- `toRoom()` とrouteが競合した場合は例外。
- `selfUnread()` が送信payloadに反映される。

## Phase 4: OAuth2

テスト項目:

- authorization URLに `client_id`、`redirect_uri`、`state` が含まれる。
- callbackでstateが検証される。
- authorization codeがtoken endpointへ送られる。
- refresh tokenでaccess tokenを更新できる。
- `TokenRepository` に保存が委譲される。
- 秘密情報が例外文字列に出ない。

## Phase 5: Message Resource拡張

対象:

- `GET /rooms/{room_id}/messages`
- `GET /rooms/{room_id}/messages/{message_id}`
- `PUT /rooms/{room_id}/messages/{message_id}`
- `DELETE /rooms/{room_id}/messages/{message_id}`
- read / unread

## Phase 6以降

次の順で進める。

1. rooms
2. members
3. tasks
4. files
5. invitation links
6. contacts
7. me / my
8. incoming requests

