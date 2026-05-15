<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use Illuminate\Notifications\Notification;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;

class ChatworkMessage extends Notification
{
    /** @var array<int, string> */
    private array $segments = [];

    private ?bool $selfUnread = null;

    private int|string|null $targetRoomId = null;

    public function __construct(?string $body = null)
    {
        if ($body !== null && $body !== '') {
            $this->segments[] = $body;
        }
    }

    public static function make(): self
    {
        return new self();
    }

    public function body(string $text): self
    {
        $this->segments[] = $text;

        return $this;
    }

    public function to(int $accountId): self
    {
        $this->segments[] = sprintf('[To:%d]', $accountId);

        return $this;
    }

    public function info(string $title, string $body): self
    {
        $this->segments[] = sprintf('[info][title]%s[/title]%s[/info]', $title, $body);

        return $this;
    }

    public function title(string $text): self
    {
        $this->segments[] = sprintf('[title]%s[/title]', $text);

        return $this;
    }

    public function code(string $text): self
    {
        $this->segments[] = sprintf('[code]%s[/code]', $text);

        return $this;
    }

    public function hr(): self
    {
        $this->segments[] = '[hr]';

        return $this;
    }

    public function plain(string $text): self
    {
        $this->segments[] = self::neutralize($text);

        return $this;
    }

    public function escape(string $text): self
    {
        return $this->plain($text);
    }

    public function selfUnread(bool $value = true): self
    {
        $this->selfUnread = $value;

        return $this;
    }

    public function toRoom(int|string $roomId): self
    {
        $this->targetRoomId = $roomId;

        return $this;
    }

    public function targetRoomId(): int|string|null
    {
        return $this->targetRoomId;
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        unset($notifiable);

        return [ChatworkChannel::class];
    }

    public function toChatwork(object $notifiable): self
    {
        unset($notifiable);

        return $this;
    }

    /**
     * @return array{body: string, self_unread?: int}
     */
    public function toPayload(): array
    {
        $body = implode("\n", $this->segments);

        return (new CreateMessageRequest($body, $this->selfUnread))->toArray();
    }

    private static function neutralize(string $text): string
    {
        return strtr($text, [
            '[' => '［',
            ']' => '］',
        ]);
    }
}
