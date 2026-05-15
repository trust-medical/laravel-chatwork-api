<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;
use TrustMedical\LaravelChatworkApi\ChatworkManager;

final class ChatworkChannel
{
    public function __construct(private readonly ChatworkManager $manager) {}

    /**
     * @return array<int, mixed>
     */
    public function send(object $notifiable, Notification $notification): array
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (manager=%s, notifiable=%s, notification=%s)',
            $this->manager::class,
            $notifiable::class,
            $notification::class,
        ));
    }
}
