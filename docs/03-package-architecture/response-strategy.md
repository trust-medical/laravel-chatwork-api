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

config の `response.mode`（デフォルト `'dto'`）は ServiceProvider の `packageRegistered()`（register 段）で `'chatwork'` シングルトン生成時に `ResponseMode::fromConfig()` を介して Manager の初期 mode を設定するためだけに使う。無効な値は黙ってフォールバックせず `ChatworkValidationException` を投げる（fail-fast）。

