# 戻り値と例外戦略

## 戻り値モード

利用側はチェーンで戻り値モードを指定できる。

```php
Chatwork::asArray();
Chatwork::asDto();
Chatwork::asCollection();
Chatwork::asResponse();
Chatwork::asPsrResponse();
Chatwork::asResult();
```

デフォルトは `asDto()` とする。

## 対応表

| Mode | 成功時 | 4xx / 5xx | 主な用途 |
| --- | --- | --- | --- |
| `asArray` | 配列 | `ChatworkRequestException` | シンプルな業務処理 |
| `asDto` | readonly DTO | `ChatworkRequestException` | 型安全な業務処理 |
| `asCollection` | `Illuminate\Support\Collection` | `ChatworkRequestException` | 一覧処理 |
| `asResponse` | `Illuminate\Http\Client\Response` | throwしない | HTTP詳細をアプリ側で扱う |
| `asPsrResponse` | `Psr\Http\Message\ResponseInterface` | throwしない | PSR連携 |
| `asResult` | `ChatworkResult` | throwしない | 失敗を値として扱う |

## 型契約（v1 公開契約）

公開 Resource メソッドの戻り値は `ResponseMode` により実行時型が変わるため、
ネイティブ署名は `: mixed` で固定する。これは v1 で恒久固定する後方互換契約であり、
後から具体型へ narrowing しない（`mixed` を受けている利用側コードを壊すため）。

`ResponseMode` はメソッド引数ではなくチェーンで決まる Manager の instance state
であるため、PHPStan の conditional return type（`@phpstan-return ($param is …)`）は
構造上適用できない。型情報は次の方針で提供する。

- 公開 Resource クラスに `@api` を付与し、サポート対象の公開表面を明示する。
- list 系メソッドは `@return list<XData>` を宣言する（`list<>` は配列互換のため、
  非 Dto モードへ切り替えても静的解析と構造的に衝突しない）。
- 単体取得系メソッドは `@return` を宣言しない。具体型を宣言すると PHPStan level 6 が
  それを honor し、全モードを検証するテスト・利用側コード（`asResult()` の
  `->failed()`、`asArray()` の添字アクセス等）と構造的に衝突するため。
  asDto() モードの型は下表で示す。

宣言された `@return` および下表の型は、いずれも **`asDto()`（デフォルト）モードの
契約**である。`asArray()` / `asCollection()` / `asResponse()` / `asPsrResponse()` /
`asResult()` へ切り替えた場合は実行時型が変わり（[対応表](#対応表)・[204 No Content](#204-no-content)
参照）、その型解釈は呼び出し側の責務となる。

### メソッド別 asDto() 戻り値型

| Resource | メソッド | asDto() 戻り値型 |
| --- | --- | --- |
| `RoomsResource` | `list()` | `list<RoomData>` |
| `RoomsResource` | `create()` | `CreatedRoom` |
| `RoomsResource` | `find()` | `RoomData` |
| `RoomsResource` | `update()` | `UpdatedRoom` |
| `RoomsResource` | `leaveRoom()` | `NoContentData` |
| `RoomsResource` | `deleteRoom()` | `NoContentData` |
| `RoomMessagesResource` | `create()` | `CreatedMessage` |
| `RoomMessagesResource` | `list()` | `list<MessageData>` |
| `RoomMessagesResource` | `find()` | `MessageData` |
| `RoomMessagesResource` | `update()` | `UpdatedMessage` |
| `RoomMessagesResource` | `deleteMessage()` | `DeletedMessage` |
| `RoomMessagesResource` | `markAsRead()` | `MarkReadResult` |
| `RoomMessagesResource` | `markAsUnread()` | `MarkUnreadResult` |
| `RoomMembersResource` | `list()` | `list<RoomMemberData>` |
| `RoomMembersResource` | `replaceMembers()` | `ReplacedRoomMembers` |
| `RoomTasksResource` | `list()` | `list<RoomTaskData>` |
| `RoomTasksResource` | `create()` | `CreatedTask` |
| `RoomTasksResource` | `find()` | `RoomTaskData` |
| `RoomTasksResource` | `updateStatus()` | `RoomTaskData` |
| `RoomFilesResource` | `list()` | `list<RoomFileData>` |
| `RoomFilesResource` | `upload()` | `UploadedRoomFile` |
| `RoomFilesResource` | `find()` | `RoomFileData` |
| `RoomLinksResource` | `find()` | `RoomLinkData` |
| `RoomLinksResource` | `create()` | `RoomLinkData` |
| `RoomLinksResource` | `update()` | `RoomLinkData` |
| `RoomLinksResource` | `deleteLink()` | `RoomLinkData` |
| `ContactsResource` | `list()` | `list<ContactData>` |
| `MeResource` | `get()` | `MyAccountData` |
| `MyResource` | `status()` | `MyStatusData` |
| `MyResource` | `tasks()` | `list<MyTaskData>` |
| `IncomingRequestsResource` | `list()` | `list<IncomingRequestData>` |
| `IncomingRequestsResource` | `accept()` | `ContactData` |
| `IncomingRequestsResource` | `decline()` | `NoContentData` |

list 系で `asDto()` 時に Chatwork が 204 を返す場合は `[]` に縮退する
（`ContactsResource::list` / `MyResource::tasks` / `IncomingRequestsResource::list`）。

拡張点 interface（`TokenRepository` / `MapsFromArray`）は `@api` を付与し、
シグネチャを v1 で恒久固定する後方互換契約とする。`ChatworkClient::send()` /
`sendList()` / `upload()` は低レベル配管であり `@internal`（後方互換保証の対象外、
公開 API は Resource 層を使う）。

## Validation Exception

送信前バリデーションの失敗は戻り値モードに関係なく例外にする。

例:

- 空のmessage body
- 65535文字を超えるmessage body
- 許可されないenum
- 5MBを超えるfile upload

想定例外:

```php
TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException
```

## Request Exception

`asArray`、`asDto`、`asCollection` では、HTTP 4xx / 5xxを独自例外に包む。

```php
TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException
```

例外には次を含める。

- status code
- response body
- parsed errors
- request method
- request path
- operationId

秘密情報は含めない。

## Result

`asResult()` は成功と失敗を値で表す。

```php
$result = Chatwork::asResult()
    ->rooms()
    ->messages()
    ->create($roomId, '本文');

if ($result->failed()) {
    $result->status();
    $result->errors();
}
```

## 204 No Content

204はエラーではない。
戻り値モードごとの扱いは次の通り。

| Mode | 204の扱い |
| --- | --- |
| `asArray` | `[]` |
| `asDto` | `NoContentData` |
| `asCollection` | 空Collection |
| `asResponse` | Laravel Responseをそのまま返す |
| `asPsrResponse` | PSR-7 Responseをそのまま返す |
| `asResult` | 成功Result、dataは `null` または `NoContentData` |

`NoContentData` は `src/Data/Responses/NoContentData.php` に readonly class として配置する。利用側から `$result->data instanceof NoContentData` で 204 を判定できる。

## PSR-7 Response

`asPsrResponse()` はPSR-7を必要とする外部連携向けに提供する。

実装方針: **Laravel HTTP Client (Guzzle) 前提**。Laravel HTTP Client は内部実装として Guzzle を利用しており、`Illuminate\Http\Client\Response::toPsrResponse()` で PSR-7 Response を取得できる。本パッケージは追加の PSR-7 実装には依存せず、Guzzle 経路をそのまま利用する。

```php
// 実装イメージ
public function toPsr(): \Psr\Http\Message\ResponseInterface
{
    return $this->illuminateResponse->toPsrResponse();
}
```

利用者が transport を差し替える場合（mock など）、PSR-7 Response が取得できないケースは `RuntimeException` を投げる方針とする。docs に「Guzzle 前提の PSR-7 取り出しである」点を README で明示する。

## 戻り値モードの状態管理

戻り値モード（`asArray` / `asDto` / `asCollection` / `asResponse` / `asPsrResponse` / `asResult`）は Manager のグローバル状態を変更せず、immutable copy を返す。

```php
$asResult = Chatwork::asResult();             // clone
$asResult->rooms()->messages()->create(...);  // Result が返る

Chatwork::rooms()->messages()->create(...);   // default mode = asDto に戻る
```

実装上は `ChatworkManager` が `protected ResponseMode $mode` を保持し、`asXxx()` 系メソッドは `clone $this` で新インスタンスを返す。`ChatworkClient` 経由で `Resources/*` に伝播し、`ResponseMapper` で参照される。

連鎖した場合は**最後に指定されたもの**が有効。

```php
Chatwork::asResult()->asArray()->rooms()->...; // asArray が有効
```

config の `response.mode`（デフォルト `'dto'`）は ServiceProvider の `packageRegistered()`（register 段）で `'chatwork'` シングルトン生成時に `ResponseMode::fromConfig()` を介して Manager の初期 mode を設定するためだけに使う。無効な値は黙ってフォールバックせず `ChatworkConfigurationException` を投げる（fail-fast。送信前入力検証ではなく設定/配線エラーであるため）。

## Octane / 常駐プロセス互換

`ChatworkManager` はコンテナ singleton としてバインドされるが、`connection()` /
`forConnection()` / `withApiToken()` / `withBearerToken()` / `asArray()`〜`asResult()` の
すべてが `clone` した新インスタンスを返し、共有 singleton を mutate しない。したがって
Laravel Octane / Swoole / キューワーカー等の常駐プロセスでもリクエスト間で connection・
認証情報・戻り値モードが漏れない。この不変条件は
`tests/Feature/ChatworkManagerImmutabilityTest.php` で回帰固定しており、将来 mutate する
with 系メソッドが追加された場合はテスト失敗として検出される（観測困難なサイレント BC の防止）。

