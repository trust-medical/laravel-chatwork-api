<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\NormalizesIntegerList;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class ReplaceRoomMembersRequest
{
    use NormalizesIntegerList;

    /**
     * @param  array<int, int>  $membersAdminIds
     * @param  array<int, int>|null  $membersMemberIds
     * @param  array<int, int>|null  $membersReadonlyIds
     *
     * @throws ChatworkValidationException members_admin_ids が空、またはいずれかのメンバー ID リストに正でない整数が含まれる場合。
     */
    public function __construct(
        public array $membersAdminIds,
        public ?array $membersMemberIds = null,
        public ?array $membersReadonlyIds = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{members_admin_ids: string, members_member_ids?: string, members_readonly_ids?: string}
     */
    public function toArray(): array
    {
        $payload = [
            'members_admin_ids' => self::idsToCsv($this->membersAdminIds),
        ];

        if ($this->membersMemberIds !== null) {
            $payload['members_member_ids'] = self::idsToCsv($this->membersMemberIds);
        }
        if ($this->membersReadonlyIds !== null) {
            $payload['members_readonly_ids'] = self::idsToCsv($this->membersReadonlyIds);
        }

        return $payload;
    }

    private function validate(): void
    {
        if (count($this->membersAdminIds) === 0) {
            throw new ChatworkValidationException(
                'members_admin_ids must contain at least one account id.',
                ['members_admin_ids' => ['must contain at least one account id']],
            );
        }
        self::assertIntegerList($this->membersAdminIds, 'members_admin_ids');

        if ($this->membersMemberIds !== null) {
            self::assertIntegerList($this->membersMemberIds, 'members_member_ids');
        }
        if ($this->membersReadonlyIds !== null) {
            self::assertIntegerList($this->membersReadonlyIds, 'members_readonly_ids');
        }
    }
}
