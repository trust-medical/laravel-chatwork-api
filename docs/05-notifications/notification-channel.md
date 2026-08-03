# Notification Channel設計

## 参照仕様

Laravel Notifications 13.x: https://laravel.com/docs/13.x/notifications

Laravelのcustom channelは、`send(object $notifiable, Notification $notification)` を持つclassとして定義できる。
通知classの `via()` からchannel class名を返すことで利用できる。

## 提供クラス

```php
TrustMedical\LaravelChatworkApi\Notifications\ChatworkChannel
TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification
TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage
TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute
```

### ChatworkNotification と ChatworkMessage の責務分離

`ChatworkMessage` は **送信payload の builder / DTO** に専念する。Notification ではない。
Chatwork チャンネルへ送るための Notification 責務は `ChatworkNotification`（abstract）に一元化する。

| クラス | 役割 | 利用シーン |
| --- | --- | --- |
| `ChatworkMessage` | 送信payload（body / self_unread / 記法）の builder / DTO。Notification ではない | `toChatwork()` の戻り値としてメッセージを組み立てる |
| `ChatworkNotification` | `Illuminate\Notifications\Notification` を継承した abstract。`via()` は `ChatworkChannel` に事前接続済み。`toChatwork(): ChatworkMessage` を実装する | Chatwork へ通知を送るすべてのケース |

具体的な実装関係:

- `ChatworkMessage` は **どのクラスも継承しない**。`via()` / `toChatwork()` は持たない。builder API（`body()` / `to()` / `info()` / `selfUnread()` / `toRoom()` 等）と `toPayload()` のみを公開する。
- `ChatworkNotification extends \Illuminate\Notifications\Notification` は abstract。利用者が継承して `toChatwork($notifiable): ChatworkMessage` を実装する。`via()` は `[ChatworkChannel::class]` を返すよう内蔵済みのため再宣言不要。
- `ChatworkChannel::send()` は `$notification->toChatwork($notifiable)` を呼び、戻り値（`ChatworkMessage`）を `RoomMessagesResource::create()` に渡す。

### `toChatwork()` の戻り値型

`toChatwork($notifiable): ChatworkMessage`（厳格な型）。

- 戻り値が `string` の場合は **許可しない**（`TypeError` または `ChatworkValidationException`）。
- 短文だけ送りたい場合も `return ChatworkMessage::make()->body('本文');` または `return new ChatworkMessage('本文');` を `toChatwork()` から返す。
- これにより `selfUnread` / `to(...)` / `info(...)` などの builder API へのアクセス経路を1つに保つ。

## 基本利用

`ChatworkMessage` は単独では送信できない。`ChatworkNotification` を継承した Notification から返す。

```php
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;

class QuickMessage extends ChatworkNotification
{
    public function __construct(private string $text) {}

    public function toChatwork(object $notifiable): ChatworkMessage
    {
        return new ChatworkMessage($this->text);
    }
}

$user->notify(new QuickMessage('本文'));
```

## 明示的なNotification

```php
class TaskAssignedNotification extends ChatworkNotification
{
    public function __construct(private Task $task) {}

    public function toChatwork(object $notifiable): ChatworkMessage
    {
        return ChatworkMessage::make()
            ->info('タスク割当', $this->task->title)
            ->selfUnread();
    }
}

$user->notify(new TaskAssignedNotification($task));
```

## Channel送信処理

`ChatworkChannel` は次の順で処理する。

1. notificationから `toChatwork($notifiable)` を呼び、`ChatworkMessage` を得る。
2. `ChatworkRoute` を解決する。
3. `ChatworkManager` からconnection付きclientを取得する。
4. `POST /rooms/{room_id}/messages` を呼ぶ。
5. Laravelの `NotificationSent` eventで参照できるよう、APIレスポンスを返す。

## via

利用者のNotification classでは次の形を許可する。

```php
public function via(object $notifiable): array
{
    return [ChatworkChannel::class];
}
```

## Queue

Laravel標準の `ShouldQueue` と `Queueable` に委譲する。
パッケージ側で独自queue制御は行わない。

## 戻り値モード

`ChatworkChannel` 内部での Resource 呼出しは **`asResult()` 固定**。

理由:

- queue worker から呼ばれる場合、例外をそのまま伝播させると stack trace 出力 + job retry が同時に発火し制御しにくい。
- `NotificationSent` event で送信結果（成功 / 失敗 / status code / errors）を観測可能にする。
- パッケージ利用側が戻り値モードを切り替える設定面は MVP では作らない（YAGNI）。後続で必要になれば config か channel オプションで導入する。

```php
// ChatworkChannel::send() 内部のイメージ
$result = $this->manager
    ->forConnection($route->connection())
    ->asResult()
    ->rooms()
    ->messages()
    ->create($route->roomId(), $message->toPayload());
```

## 失敗時の再送ポリシー

HTTP 失敗時の挙動は status code に応じて分岐する。

| Status | channel の挙動 | queue への影響 |
| --- | --- | --- |
| 2xx | 成功 `ChatworkResult` を `NotificationSent` event に載せる | 完了 |
| 4xx（401 / 403 / 404 / 400） | `ChatworkRequestException` を **throw** | permanent failure（queue retry させない） |
| 429 | `ChatworkRequestException` を throw | queue retry に委譲（Laravel の retry/backoff で `x-ratelimit-reset` を考慮可能） |
| 5xx / network error | `ChatworkRequestException` を throw | queue retry に委譲 |

`asResult()` 固定だが、5xx / 429 / network error については channel 側で例外に変換して投げ直す（queue retry を作動させるため）。4xx は permanent failure として扱い、retry を抑止する。

```php
// 実装イメージ
if ($result->failed()) {
    $status = $result->status();
    if ($status >= 500 || $status === 429 || $status === null) {
        throw $result->toException(); // queue retry に委譲
    }
    if ($status >= 400) {
        throw $result->toException(); // permanent failure として通知
        // queue retry が不要であることは Laravel の job 設定（tries=1 or hasMaxExceptions）で表現
    }
}
```

利用者側は `ShouldQueue` 実装の Notification で `$tries`、`backoff()`、`failed()` を使って再送と最終失敗ハンドリングを制御できる。

