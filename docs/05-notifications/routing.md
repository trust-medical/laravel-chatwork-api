# Notification Routing設計

## 基本方針

Laravelの `routeNotificationFor{Channel}` パターンに従い、notifiableからChatwork送信先を解決する。
単純なroom IDと、connectionを含む `ChatworkRoute` の両方を許可する。

## 単純なroom ID

```php
public function routeNotificationForChatwork(): int|string
{
    return $this->chatwork_room_id;
}
```

## ChatworkRoute

複数ワークスペースや動的connectionに対応するため、推奨は `ChatworkRoute`。

```php
public function routeNotificationForChatwork(): ChatworkRoute
{
    return ChatworkRoute::room($this->chatwork_room_id)
        ->connection('sales');
}
```

動的connection:

```php
public function routeNotificationForChatwork(): ChatworkRoute
{
    return ChatworkRoute::room($this->chatwork_room_id)
        ->using(Connection::make(
            name: 'tenant-'.$this->tenant_id,
            credentials: Credentials::bearerToken($this->chatwork_access_token),
        ));
}
```

## On-Demand Notification

```php
Notification::route(
    'chatwork',
    ChatworkRoute::room($roomId)->connection('sales')
)->notify(
    ChatworkMessage::make()->body('本文')
);
```

単純形:

```php
Notification::route('chatwork', $roomId)
    ->notify(new ChatworkMessage('本文'));
```

## 配列指定

複数room送信や拡張用にarrayを許可する。

```php
return [
    ChatworkRoute::room($roomA)->connection('sales'),
    ChatworkRoute::room($roomB)->connection('support'),
];
```

### MVP 仕様: fail-fast

初期実装は **fail-fast**。配列の先頭から順次送信し、最初の失敗で処理を中断して例外を投げる。

| ケース | 挙動 |
| --- | --- |
| 全件成功 | すべての `ChatworkResult` を集めて返す（`array<ChatworkResult>`） |
| 途中で失敗 | 失敗時点で `ChatworkRequestException` を throw。残りの送信は **行わない** |
| 4xx失敗 | `ChatworkRequestException`、queue は retry しない |
| 5xx / 429 | `ChatworkRequestException`、queue retry に委譲（前半成功分は重複送信されうる点を docstring に明示） |

集約結果（partial success の値表現、各失敗の inspect API）は後続フェーズで設計する。MVP では「複数room送信は同質な送信先（権限・connection が同等）でのみ使う」ことを README で案内する。

`ChatworkChannel` 内では `asResult()` 固定で 1 件ずつ送信し、`$result->failed()` を見て分岐する。実装イメージ:

```php
$results = [];
foreach ($routes as $route) {
    $result = $this->sendOne($route, $message);
    if ($result->failed()) {
        // queue retry すべき status は throw、permanent は throw（再送抑止は Notification 側 tries=1）
        throw $result->toException();
    }
    $results[] = $result;
}
return $results;
```

## Route解決優先順位

1. `ChatworkMessage` に `toRoom()` が明示されている場合。
2. `routeNotificationForChatwork()` の戻り値。
3. `Notification::route('chatwork', ...)` の指定。

同時に複数指定された場合の衝突は、実装時に例外にする。
暗黙に上書きすると誤送信につながるため。

### 衝突検知の所在

衝突検知ロジックは `ChatworkChannel::send()` の冒頭で実行する。`ChatworkRoute` 自身は **状態を持つだけ**、衝突判定はしない。

検知タイミング:

- `ChatworkMessage::toRoom()` が呼ばれていて、かつ `routeNotificationForChatwork()` または `Notification::route('chatwork', ...)` から空でない route が返ってきた場合 → `ChatworkRoutingException`
- `routeNotificationForChatwork()` と `Notification::route('chatwork', ...)` の両方が空でない場合 → `ChatworkRoutingException`
- 配列内に同じ `room_id` × `connection` の組が重複している場合 → 警告ログ（重複送信は許容するが目立たせる）

`ChatworkRoutingException` は `ChatworkValidationException` を継承して、送信前バリデーション扱いとする（戻り値モードに関係なく throw）。

