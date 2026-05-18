<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Chatwork チャンネル経由で配信される notification の基底クラス。
 *
 * サブクラスは toChatwork() を実装してメッセージを組み立てる。via() は
 * ChatworkChannel に事前接続されているため、サブクラスで再宣言する必要はない。
 */
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
