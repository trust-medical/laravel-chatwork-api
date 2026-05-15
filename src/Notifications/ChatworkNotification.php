<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;

abstract class ChatworkNotification extends Notification
{
    abstract public function toChatwork(object $notifiable): ChatworkMessage;

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [ChatworkChannel::class];
    }
}
