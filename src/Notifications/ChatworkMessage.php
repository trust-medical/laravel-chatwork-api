<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Notifications;

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Chatwork メッセージのビルダー / DTO。
 *
 * これは Notification ではない。Chatwork チャンネルへ送信するには、
 * {@see ChatworkNotification} を継承するか、`via()` で ChatworkChannel を返し
 * `toChatwork($notifiable): ChatworkMessage` を実装した Notification から本ビルダーを返すこと。
 */
final class ChatworkMessage
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

    /**
     * Chatwork タグの角括弧を全角文字に無害化してテキストを追加する。
     * 意図しないマークアップ / タグインジェクションを防止する。
     */
    public function plain(string $text): self
    {
        $this->segments[] = self::neutralize($text);

        return $this;
    }

    /**
     * plain() のエイリアス: タグ無害化済みテキストを追加する。
     *
     * @see self::plain()
     */
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
     * 蓄積したセグメントを Chatwork メッセージ送信ペイロードへ変換する。
     *
     * 少なくとも 1 回 `body()` 系で本文を追加していること。本文が空のまま
     * 呼ぶと送信前バリデーションで失敗する。
     *
     * @return array{body: string, self_unread?: 0|1}
     *
     * @throws ChatworkValidationException 本文が空、または文字数上限を超える場合。
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
