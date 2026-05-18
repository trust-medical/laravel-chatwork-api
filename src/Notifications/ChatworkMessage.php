<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;

/**
 * Chatwork メッセージのビルダー / DTO。
 *
 * これは Notification ではない。Chatwork チャンネルへ送信するには、
 * {@see ChatworkNotification} を継承するか、`via()` で ChatworkChannel を返し
 * `toChatwork($notifiable): ChatworkMessage` を実装した Notification から本ビルダーを返すこと。
 */
class ChatworkMessage
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

    /** @return $this */
    public function body(string $text): self
    {
        $this->segments[] = $text;

        return $this;
    }

    /** @return $this */
    public function to(int $accountId): self
    {
        $this->segments[] = sprintf('[To:%d]', $accountId);

        return $this;
    }

    /** @return $this */
    public function info(string $title, string $body): self
    {
        $this->segments[] = sprintf('[info][title]%s[/title]%s[/info]', $title, $body);

        return $this;
    }

    /** @return $this */
    public function title(string $text): self
    {
        $this->segments[] = sprintf('[title]%s[/title]', $text);

        return $this;
    }

    /** @return $this */
    public function code(string $text): self
    {
        $this->segments[] = sprintf('[code]%s[/code]', $text);

        return $this;
    }

    /** @return $this */
    public function hr(): self
    {
        $this->segments[] = '[hr]';

        return $this;
    }

    /**
     * Chatwork タグの角括弧を全角文字に無害化してテキストを追加する。
     * 意図しないマークアップ / タグインジェクションを防止する。
     *
     * @return $this
     */
    public function plain(string $text): self
    {
        $this->segments[] = self::neutralize($text);

        return $this;
    }

    /**
     * plain() のエイリアス: タグ無害化済みテキストを追加する。
     *
     * @return $this
     */
    public function escape(string $text): self
    {
        return $this->plain($text);
    }

    /** @return $this */
    public function selfUnread(bool $value = true): self
    {
        $this->selfUnread = $value;

        return $this;
    }

    /** @return $this */
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
     * @return array{body: string, self_unread?: 0|1}
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
