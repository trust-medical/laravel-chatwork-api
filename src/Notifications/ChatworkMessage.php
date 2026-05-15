<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;

class ChatworkMessage extends Notification
{
    public function __construct(?string $body = null)
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (body_length=%d)', strlen($body ?? '')));
    }

    public static function make(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function body(string $text): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (length=%d)', strlen($text)));
    }

    public function to(int $accountId): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (accountId=%d)', $accountId));
    }

    public function info(string $title, string $body): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (title=%s)', $title) . ' body=' . strlen($body));
    }

    public function title(string $text): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (length=%d)', strlen($text)));
    }

    public function code(string $text): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (length=%d)', strlen($text)));
    }

    public function hr(): self
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    public function plain(string $text): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (length=%d)', strlen($text)));
    }

    public function escape(string $text): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (length=%d)', strlen($text)));
    }

    public function selfUnread(bool $value = true): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (value=%s)', $value ? 'true' : 'false'));
    }

    public function toRoom(int|string $roomId): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (roomId=%s)', (string) $roomId));
    }

    public function targetRoomId(): int|string|null
    {
        throw new \LogicException('not implemented in Phase 0');
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (notifiable=%s)', $notifiable::class));
    }

    public function toChatwork(object $notifiable): self
    {
        throw new \LogicException(sprintf('not implemented in Phase 0 (notifiable=%s)', $notifiable::class));
    }

    /**
     * @return array<string, int|string>
     */
    public function toPayload(): array
    {
        throw new \LogicException('not implemented in Phase 0');
    }
}
