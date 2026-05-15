<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class MessageData
{
    public function __construct(
        public string $messageId,
        public SimpleAccount $account,
        public string $body,
        public int $sendTime,
        public int $updateTime,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $account = $data['account'] ?? [];

        return new self(
            messageId: (string) ($data['message_id'] ?? ''),
            account: SimpleAccount::fromArray(is_array($account) ? $account : []),
            body: (string) ($data['body'] ?? ''),
            sendTime: (int) ($data['send_time'] ?? 0),
            updateTime: (int) ($data['update_time'] ?? 0),
        );
    }
}
