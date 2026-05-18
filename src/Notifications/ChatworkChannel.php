<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;
use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Http\Result;

final class ChatworkChannel
{
    public function __construct(private readonly ChatworkManager $manager) {}

    /**
     * 解決されたすべての Chatwork ルートに通知を送信する。
     *
     * チャンネルは asResult() モード固定。失敗 Result（4xx / 5xx / 429）はいずれも
     * ChatworkRequestException に変換して throw し、残りのルート処理を中断する。
     * この throw 自体が queue retry のトリガー: transient な失敗（5xx / 429 /
     * ネットワークエラー）は Laravel キューワーカーの retry ポリシーに委譲され、
     * 4xx は実質 permanent failure として扱われる（再試行しても解消しない）。
     *
     * @param  object  $notifiable  通知対象。routeNotificationFor('chatwork', ...) を公開していてもよい
     * @param  Notification  $notification  toChatwork($notifiable): ChatworkMessage を定義しなければならない
     * @return array<int, Result> ルート順に並んだ、成功した Result の配列
     *
     * @throws ChatworkRoutingException toChatwork() が存在しない/無効な場合、またはルート競合/ルート未設定の場合
     * @throws ChatworkRequestException いずれかのルートが失敗 HTTP（4xx / 5xx / 429）を返した場合
     */
    public function send(object $notifiable, Notification $notification): array
    {
        $message = $this->resolveMessage($notifiable, $notification);
        $routes = $this->resolveRoutes($notifiable, $notification, $message);

        $results = [];
        foreach ($routes as $route) {
            $result = $this->sendOne($route, $message);
            if ($result->failed()) {
                throw $result->toException();
            }
            $results[] = $result;
        }

        return $results;
    }

    /**
     * @throws ChatworkRoutingException notification に toChatwork() が存在しない場合、または ChatworkMessage を返さない場合
     */
    private function resolveMessage(object $notifiable, Notification $notification): ChatworkMessage
    {
        if (! method_exists($notification, 'toChatwork')) {
            throw new ChatworkRoutingException(
                sprintf('%s does not define toChatwork(); cannot send via Chatwork channel.', $notification::class),
                ['notification' => ['missing toChatwork()']],
            );
        }

        $message = $notification->toChatwork($notifiable);

        if (! $message instanceof ChatworkMessage) {
            throw new ChatworkRoutingException(
                sprintf('%s::toChatwork() must return a ChatworkMessage instance.', $notification::class),
                ['notification' => ['toChatwork() must return ChatworkMessage']],
            );
        }

        return $message;
    }

    /**
     * 送信先ルートを解決する。message の toRoom() を notifiable の routeNotificationForChatwork() より優先する — 両方指定は競合エラー。
     *
     * @return array<int, ChatworkRoute>
     *
     * @throws ChatworkRoutingException 両方のルートソースが設定されている場合、またはルートが一つも存在しない場合
     */
    private function resolveRoutes(object $notifiable, Notification $notification, ChatworkMessage $message): array
    {
        $fromMessage = $message->targetRoomId();
        $fromNotifiable = $this->routeFromNotifiable($notifiable, $notification);
        $hasNotifiableRoute = $fromNotifiable !== null && $fromNotifiable !== [] && $fromNotifiable !== '';

        if ($fromMessage !== null) {
            if ($hasNotifiableRoute) {
                throw new ChatworkRoutingException(
                    'ChatworkMessage::toRoom() conflicts with routeNotificationForChatwork() / Notification::route(\'chatwork\', ...).',
                    ['route' => ['cannot set both toRoom and routeNotificationFor']],
                );
            }

            return [ChatworkRoute::room($fromMessage)];
        }

        if (! $hasNotifiableRoute) {
            throw new ChatworkRoutingException(
                'No Chatwork route was provided (set toRoom(), routeNotificationForChatwork(), or Notification::route(\'chatwork\', ...)).',
                ['route' => ['no room_id available']],
            );
        }

        return $this->normalizeRoutes($fromNotifiable);
    }

    private function routeFromNotifiable(object $notifiable, Notification $notification): mixed
    {
        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return null;
        }

        /** @var mixed $route */
        $route = $notifiable->routeNotificationFor('chatwork', $notification);

        return $route;
    }

    /**
     * 生のルート値（ChatworkRoute、int/string のルーム ID、またはそれらのネスト配列）をフラットな ChatworkRoute リストに展開する。
     *
     * @return array<int, ChatworkRoute>
     *
     * @throws ChatworkRoutingException サポート外の型の要素が含まれる場合
     */
    private function normalizeRoutes(mixed $raw): array
    {
        if ($raw instanceof ChatworkRoute) {
            return [$raw];
        }

        if (is_int($raw) || is_string($raw)) {
            return [ChatworkRoute::room($raw)];
        }

        if (is_array($raw)) {
            $routes = [];
            foreach ($raw as $item) {
                $routes = array_merge($routes, $this->normalizeRoutes($item));
            }

            return $routes;
        }

        throw new ChatworkRoutingException(
            sprintf('Unsupported Chatwork route type: %s', get_debug_type($raw)),
            ['route' => ['unsupported type']],
        );
    }

    /**
     * 単一ルートにメッセージを投稿する。connection の選択は優先順位に従う: 明示 Connection > 名前付き connection > デフォルト。
     */
    private function sendOne(ChatworkRoute $route, ChatworkMessage $message): Result
    {
        $manager = $this->manager->asResult();

        if ($route->getConnection() !== null) {
            $manager = $manager->forConnection($route->getConnection());
        } elseif ($route->connectionName() !== null) {
            $manager = $manager->connection($route->connectionName());
        } else {
            $manager = $manager->connection();
        }

        $payload = $message->toPayload();
        $selfUnread = array_key_exists('self_unread', $payload) ? (bool) $payload['self_unread'] : null;

        /** @var Result $result */
        $result = $manager->rooms()->messages()->create((int) $route->roomId(), $payload['body'], $selfUnread);

        return $result;
    }
}
