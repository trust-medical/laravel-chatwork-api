<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Resources;

use TrustMedical\LaravelChatworkApi\ChatworkClient;
use TrustMedical\LaravelChatworkApi\Data\Requests\CreateMessageRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\MarkAsUnreadRequest;
use TrustMedical\LaravelChatworkApi\Data\Requests\UpdateMessageRequest;
use TrustMedical\LaravelChatworkApi\Data\Responses\CreatedMessage;
use TrustMedical\LaravelChatworkApi\Data\Responses\DeletedMessage;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkReadResult;
use TrustMedical\LaravelChatworkApi\Data\Responses\MarkUnreadResult;
use TrustMedical\LaravelChatworkApi\Data\Responses\MessageData;
use TrustMedical\LaravelChatworkApi\Data\Responses\UpdatedMessage;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Chatwork `/rooms/{room_id}/messages` エンドポイントグループ（一覧取得・単件取得・作成・更新・削除・既読・未読）の公開 API。
 *
 * @api 公開 API。宣言戻り値型は asDto() モード契約（他モードは実行時型が変わる。型対応表は README / docs を参照）。
 */
final class RoomMessagesResource
{
    public function __construct(private readonly ChatworkClient $client) {}

    /**
     * ルームに新しいメッセージを投稿する (POST /rooms/{room_id}/messages)。
     *
     * 本文は送信前に検証される（空でないこと、Chatwork の文字数上限以内であること）ため、
     * 不正な本文はレスポンスモードに関わらず即失敗する。
     *
     * @throws ChatworkValidationException $body が空、または Chatwork の文字数上限を超えている場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function create(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        $request = new CreateMessageRequest($body, $selfUnread);

        return $this->client->send(
            'POST',
            sprintf('/rooms/%d/messages', $roomId),
            $request->toArray(),
            CreatedMessage::class,
            'createRoomMessage',
        );
    }

    /**
     * ルームの最近のメッセージ一覧を取得する (GET /rooms/{room_id}/messages)。
     *
     * $force = true を渡すと、Chatwork の「前回取得以降のメッセージのみ返す」挙動を
     * バイパスして常に最新 100 件を取得する。
     *
     * @return list<MessageData> asDto() 契約。他モードは ResponseMode に従い実行時型が変わる
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function list(int $roomId, ?bool $force = null): mixed
    {
        $query = $force === true ? ['force' => 1] : [];

        return $this->client->sendList(
            'GET',
            sprintf('/rooms/%d/messages', $roomId),
            $query,
            MessageData::class,
            'listRoomMessages',
        );
    }

    /**
     * 指定した ID のメッセージを 1 件取得する (GET /rooms/{room_id}/messages/{message_id})。
     *
     * @throws ChatworkValidationException $messageId が正の整数文字列でない場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function find(int $roomId, string $messageId): mixed
    {
        $this->assertMessageId($messageId);

        return $this->client->send(
            'GET',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            [],
            MessageData::class,
            'getRoomMessage',
        );
    }

    /**
     * 既存のメッセージ本文を上書きする (PUT /rooms/{room_id}/messages/{message_id})。
     *
     * 新しい本文は送信前に検証される（空でないこと、Chatwork の文字数上限以内であること）ため、
     * 不正な本文はレスポンスモードに関わらず即失敗する。
     *
     * @throws ChatworkValidationException $messageId が正の整数文字列でない、$body が空、または Chatwork の文字数上限を超えている場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function update(int $roomId, string $messageId, string $body): mixed
    {
        $this->assertMessageId($messageId);

        $request = new UpdateMessageRequest($body);

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            $request->toArray(),
            UpdatedMessage::class,
            'updateRoomMessage',
        );
    }

    /**
     * 指定したメッセージを完全に削除する (DELETE /rooms/{room_id}/messages/{message_id})。
     *
     * @throws ChatworkValidationException $messageId が正の整数文字列でない場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function deleteMessage(int $roomId, string $messageId): mixed
    {
        $this->assertMessageId($messageId);

        return $this->client->send(
            'DELETE',
            sprintf('/rooms/%d/messages/%s', $roomId, $messageId),
            [],
            DeletedMessage::class,
            'deleteRoomMessage',
        );
    }

    /**
     * メッセージを既読にする (PUT /rooms/{room_id}/messages/read)。
     *
     * $messageId に null または空文字を渡すと、現時点までの全メッセージを既読にする。
     * {@see self::markAsUnread()} とは異なり、空のメッセージ ID はここでは受け入れられ、
     * バリデーションエラーにはならずフィールドを省略して送信する。
     *
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function markAsRead(int $roomId, ?string $messageId = null): mixed
    {
        // Chatwork は message_id の省略を「現時点までの全メッセージを既読にする」と解釈するため、
        // null と空文字はいずれも「フィールドを省略する」を意味する。
        // これは markAsUnread との意図的な差異であり、markAsUnread では
        // message_id が必須かつ空文字は送信前に弾かれる。
        $payload = $messageId !== null && $messageId !== ''
            ? ['message_id' => $messageId]
            : [];

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/read', $roomId),
            $payload,
            MarkReadResult::class,
            'markRoomMessagesAsRead',
        );
    }

    /**
     * 指定メッセージ以降を未読にする
     * (PUT /rooms/{room_id}/messages/unread)。
     *
     * $messageId は必須であり送信前に検証される。空文字は拒否される
     * （空文字を許容する {@see self::markAsRead()} との対比）。
     *
     * @throws ChatworkValidationException $messageId が空文字の場合。
     * @throws ChatworkRequestException スローモード (asArray/asDto/asCollection) で 4xx/5xx が返った場合。
     */
    public function markAsUnread(int $roomId, string $messageId): mixed
    {
        $request = new MarkAsUnreadRequest($messageId);

        return $this->client->send(
            'PUT',
            sprintf('/rooms/%d/messages/unread', $roomId),
            $request->toArray(),
            MarkUnreadResult::class,
            'markRoomMessagesAsUnread',
        );
    }

    /**
     * message_id を URL パスへ埋め込む前に正の整数文字列であることを検証する。
     *
     * Chatwork の message_id は 64bit を超えうるため int 化はせず文字列のまま
     * 扱う。空・非数字・先頭ゼロ（曖昧表現）はパスインジェクション/誤操作を
     * 招くため送信前に拒否する。
     *
     * @throws ChatworkValidationException message_id が正の整数文字列でない場合。
     */
    private function assertMessageId(string $messageId): void
    {
        if ($messageId === '' || ! ctype_digit($messageId) || $messageId[0] === '0') {
            throw new ChatworkValidationException(
                'message_id must be a positive integer string.',
                ['message_id' => ['must be a positive integer string']],
            );
        }
    }
}
