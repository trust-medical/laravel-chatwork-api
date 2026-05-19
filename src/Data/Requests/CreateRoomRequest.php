<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

use TrustMedical\LaravelChatworkApi\Data\Requests\Concerns\NormalizesIntegerList;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

final readonly class CreateRoomRequest
{
    use NormalizesIntegerList;

    private const int NAME_MIN = 1;

    private const int NAME_MAX = 255;

    private const int LINK_CODE_MAX = 50;

    /**
     * @param  array<int, int>  $membersAdminIds
     * @param  array<int, int>|null  $membersMemberIds
     * @param  array<int, int>|null  $membersReadonlyIds
     *
     * @throws ChatworkValidationException 名前の長さ、link_code の長さ、またはメンバー ID リストが不正な場合。
     */
    public function __construct(
        public string $name,
        public array $membersAdminIds,
        public ?string $description = null,
        public ?IconPreset $iconPreset = null,
        public ?bool $link = null,
        public ?string $linkCode = null,
        public ?bool $linkNeedAcceptance = null,
        public ?array $membersMemberIds = null,
        public ?array $membersReadonlyIds = null,
    ) {
        $this->validate();
    }

    /**
     * @return array{
     *     name: string,
     *     members_admin_ids: string,
     *     description?: string,
     *     icon_preset?: string,
     *     link?: 0|1,
     *     link_code?: string,
     *     link_need_acceptance?: 0|1,
     *     members_member_ids?: string,
     *     members_readonly_ids?: string
     * }
     */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'members_admin_ids' => self::idsToCsv($this->membersAdminIds),
        ];

        if ($this->description !== null) {
            $payload['description'] = $this->description;
        }
        if ($this->iconPreset !== null) {
            $payload['icon_preset'] = $this->iconPreset->value;
        }
        if ($this->link !== null) {
            $payload['link'] = $this->link ? 1 : 0;
        }
        if ($this->linkCode !== null) {
            $payload['link_code'] = $this->linkCode;
        }
        if ($this->linkNeedAcceptance !== null) {
            $payload['link_need_acceptance'] = $this->linkNeedAcceptance ? 1 : 0;
        }
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
        $nameLength = mb_strlen($this->name);
        if ($nameLength < self::NAME_MIN) {
            throw new ChatworkValidationException(
                'Room name must not be empty.',
                ['name' => ['must not be empty']],
            );
        }
        if ($nameLength > self::NAME_MAX) {
            throw new ChatworkValidationException(
                sprintf('Room name must be %d characters or less.', self::NAME_MAX),
                ['name' => [sprintf('must be %d characters or less', self::NAME_MAX)]],
            );
        }

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

        if ($this->linkCode !== null && mb_strlen($this->linkCode) > self::LINK_CODE_MAX) {
            throw new ChatworkValidationException(
                sprintf('link_code must be %d characters or less.', self::LINK_CODE_MAX),
                ['link_code' => [sprintf('must be %d characters or less', self::LINK_CODE_MAX)]],
            );
        }
    }
}
