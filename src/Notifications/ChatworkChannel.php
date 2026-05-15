<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;
use TrustMedical\LaravelChatworkApi\ChatworkManager;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRoutingException;
use TrustMedical\LaravelChatworkApi\Http\Result;

final class ChatworkChannel
{
    public function __construct(private readonly ChatworkManager $manager) {}

    /**
     * @return array<int, Result>
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
     * @return array<int, ChatworkRoute>
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
     * @return array<int, ChatworkRoute>
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
