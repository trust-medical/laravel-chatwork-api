# Phase 3: Notification Channel

## 目的

Laravel Notification 経由での Chatwork 送信を完成させる。`ChatworkChannel` / `ChatworkMessage` / `ChatworkNotification` / `ChatworkRoute` の 4 要素と、`routeNotificationForChatwork()` / `Notification::route('chatwork', ...)` の両ルーティング、配列 route、衝突検知まで含める。

## 前提

- Phase 2 完了（`RoomMessagesResource::create()` が動作する）。

## DoD

- 次のテストが緑:
  - `it('$user->notify(new ChatworkMessage(...))') ` で POST される`
  - `it('Notification::route(\'chatwork\', $roomId)->notify(...)')` で POST される
  - `ChatworkRoute::room()->connection()` で対象 connection に送信される
  - 配列 route（複数 room）で fail-fast 動作する
  - `toRoom()` と `routeNotificationForChatwork()` が衝突した場合 `ChatworkRoutingException`
  - 4xx で `ChatworkRequestException`、5xx/429 で queue retry 可能な例外形
  - 戻り値モードが内部的に `asResult()` 固定で動作
- Pint / PHPStan / Pest 緑、カバレッジ 80%+

## TODO

### 3-1. ChatworkMessage builder

- [ ] `tests/Unit/Notifications/ChatworkMessageTest.php`
  - [Red] `it('builds body from constructor')`
  - [Red] `it('builds body via make()->body()')`
  - [Red] `it('appends [To:account_id] via to(int)')`
  - [Red] `it('renders [info][title]...[/title]...[/info] via info(title, body)')`
  - [Red] `it('renders [title]...[/title]')`
  - [Red] `it('renders [code]...[/code]')`
  - [Red] `it('renders hr')`
  - [Red] `it('escapes brackets in plain() mode')`
  - [Red] `it('toggles selfUnread()')`
  - [Red] `it('captures toRoom() target')`
  - [Red] `it('via() returns [ChatworkChannel::class]')`
  - [Red] `it('toChatwork() returns self')`
  - [Red] `it('toPayload() returns body and self_unread')`
  - [Red] `it('rejects body over 65535 chars at toPayload time')`
- [ ] [Green] `src/Notifications/ChatworkMessage.php`
  - extends `Illuminate\Notifications\Notification`
  - fluent: `make()`, `body()`, `to()`, `info()`, `title()`, `code()`, `hr()`, `plain()`, `escape()`, `selfUnread()`, `toRoom()`
  - `via()`, `toChatwork()` 実装
  - `toPayload(): array` — `body` / `self_unread` のみ
  - `targetRoomId(): ?int`
- [ ] [Refactor] 記法レンダリングを `ChatworkSyntaxRenderer` private class に分離

参照: `docs/05-notifications/chatwork-message-builder.md`、`docs/01-requirements/functional-requirements.md` の Chatwork記法スコープ境界

### 3-2. ChatworkNotification abstract

- [ ] `tests/Unit/Notifications/ChatworkNotificationTest.php`
  - [Red] `it('forces subclass to implement toChatwork')` — `Reflection` で abstract method を確認
  - [Red] `it('via returns [ChatworkChannel::class]')`
- [ ] [Green] `src/Notifications/ChatworkNotification.php`
  - `abstract class ChatworkNotification extends \Illuminate\Notifications\Notification`
  - `abstract public function toChatwork(object $notifiable): ChatworkMessage`
  - `public function via(object $notifiable): array { return [ChatworkChannel::class]; }`

参照: `docs/05-notifications/notification-channel.md` の責務分離セクション

### 3-3. ChatworkRoute

- [ ] `tests/Unit/Notifications/ChatworkRouteTest.php`
  - [Red] `it('room($id)->connection($name)')`
  - [Red] `it('room($id)->using(Connection $c)')`
  - [Red] `it('exposes roomId / connectionName / connection')`
- [ ] [Green] `src/Notifications/ChatworkRoute.php`
  - immutable value object
  - `static room(int|string $roomId): self`
  - `connection(string $name): self` — clone
  - `using(Connection $c): self` — clone
  - getter: `roomId(): int|string`, `connectionName(): ?string`, `connection(): ?Connection`

参照: `docs/05-notifications/routing.md`

### 3-4. ChatworkChannel::send()

- [ ] `tests/Feature/Notifications/ChannelSendTest.php`
  - [Red] `it('sends to user.routeNotificationForChatwork() room id')`
  - [Red] `it('sends to Notification::route(chatwork, $roomId) room id')`
  - [Red] `it('uses ChatworkRoute connection name for target connection')`
  - [Red] `it('uses ChatworkRoute Connection value object when present')`
  - [Red] `it('uses ChatworkMessage::toRoom() when set')`
  - [Red] `it('NotificationSent event receives ChatworkResult in response')`
  - [Red] `it('uses asResult() mode internally')` — 4xx でも throw ではなく Result
  - [Red] `it('throws ChatworkRequestException for 5xx (queue retry)')`
  - [Red] `it('throws ChatworkRequestException for 429 (queue retry)')`
  - [Red] `it('throws ChatworkRequestException for 4xx (permanent failure)')`
- [ ] [Green] `src/Notifications/ChatworkChannel.php`
  - `__construct(private ChatworkManager $manager)`
  - `send(object $notifiable, Notification $notification): array<ChatworkResult>`
  - 内部処理:
    1. `$notification->toChatwork($notifiable)` で `ChatworkMessage` 取得（戻り値型を `instanceof ChatworkMessage` で厳格チェック）
    2. route 解決（後述 3-5）
    3. 配列 route なら fail-fast ループ
    4. `$this->manager->forConnection(...)` / `connection(...)` → `asResult()` → `rooms()->messages()->create($roomId, $message->toPayload())`
    5. `$result->failed()` の場合、status code を判定して 5xx/429 / 4xx / network error で例外を投げ分け
- [ ] [Refactor] route 解決ロジックを `private function resolveRoutes(...): array<ChatworkRoute>` に分離

参照: `docs/05-notifications/notification-channel.md` の Channel送信処理 / 失敗時の再送ポリシー

### 3-5. Route 解決と衝突検知

- [ ] `tests/Feature/Notifications/RouteResolutionTest.php`
  - [Red] `it('throws ChatworkRoutingException when toRoom and routeNotificationForChatwork both set')`
  - [Red] `it('throws ChatworkRoutingException when routeNotificationForChatwork and Notification::route both set')`
  - [Red] `it('logs warning for duplicate room+connection in array route')`
  - [Red] `it('priority: toRoom > routeNotificationForChatwork > Notification::route')`
- [ ] [Green] `ChatworkChannel::resolveRoutes(object $notifiable, ChatworkMessage $message): array<ChatworkRoute>`
- [ ] [Green] `Exceptions/ChatworkRoutingException` 利用箇所追加

参照: `docs/05-notifications/routing.md`

### 3-6. 配列 route fail-fast

- [ ] `tests/Feature/Notifications/ArrayRouteFailFastTest.php`
  - [Red] `it('sends to all rooms in order when all succeed')`
  - [Red] `it('stops at first failure and throws')` — 残り room には送信されない（`Http::assertNotSent`）
  - [Red] `it('5xx in middle leaves earlier rooms successfully sent')`
- [ ] [Green] `ChatworkChannel::send()` の配列ループに fail-fast 実装

参照: `docs/05-notifications/routing.md` の MVP仕様: fail-fast

### 3-7. ServiceProvider 登録

- [ ] `tests/Feature/ServiceProviderTest.php` に追加テスト
  - [Red] `it('registers ChatworkChannel in container')`
  - [Red] `it('extends NotificationChannelManager with chatwork driver')`
- [ ] [Green] `ChatworkServiceProvider::boot()` で `Notification::resolved(fn($manager) => $manager->extend('chatwork', fn() => $this->app->make(ChatworkChannel::class)))`

参照: Laravel 13 Notifications custom channel docs

### 3-8. queue 連携テスト

- [ ] `tests/Feature/Notifications/QueueIntegrationTest.php`
  - [Red] `it('runs synchronously when notification does not ShouldQueue')`
  - [Red] `it('dispatches to queue when notification ShouldQueue')` — `Bus::fake()` または queue driver=sync
  - [Red] `it('queue retry counter increases on 5xx')` — Job 単体テスト
- [ ] [Green] 既存実装で動作する想定（特別な実装は不要、Laravel 標準に乗る）

### 3-9. 検証

- [ ] すべてのテストが緑
- [ ] `code-reviewer` agent で CRITICAL/HIGH 解消
- [ ] commit 粒度: 概ね各小節 1〜2 コミット

## 完了後

→ Phase 4（OAuth2）または Phase 5（Messages 残）へ。Phase 2 完了後なら並行可能。
