<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;

final readonly class MyAccountData implements MapsFromArray
{
    public function __construct(
        public int $accountId,
        public int $roomId,
        public string $name,
        public string $chatworkId,
        public int $organizationId,
        public string $organizationName,
        public string $department,
        public string $title,
        public string $url,
        public string $introduction,
        public string $mail,
        public string $telOrganization,
        public string $telExtension,
        public string $telMobile,
        public string $skype,
        public string $facebook,
        public string $twitter,
        public string $avatarImageUrl,
        public string $loginMail,
    ) {}

    /**
     * @param  array{
     *     account_id?: int|string,
     *     room_id?: int|string,
     *     name?: string,
     *     chatwork_id?: string,
     *     organization_id?: int|string,
     *     organization_name?: string,
     *     department?: string,
     *     title?: string,
     *     url?: string,
     *     introduction?: string,
     *     mail?: string,
     *     tel_organization?: string,
     *     tel_extension?: string,
     *     tel_mobile?: string,
     *     skype?: string,
     *     facebook?: string,
     *     twitter?: string,
     *     avatar_image_url?: string,
     *     login_mail?: string
     * }  $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            accountId: (int) ($data['account_id'] ?? 0),
            roomId: (int) ($data['room_id'] ?? 0),
            name: (string) ($data['name'] ?? ''),
            chatworkId: (string) ($data['chatwork_id'] ?? ''),
            organizationId: (int) ($data['organization_id'] ?? 0),
            organizationName: (string) ($data['organization_name'] ?? ''),
            department: (string) ($data['department'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            introduction: (string) ($data['introduction'] ?? ''),
            mail: (string) ($data['mail'] ?? ''),
            telOrganization: (string) ($data['tel_organization'] ?? ''),
            telExtension: (string) ($data['tel_extension'] ?? ''),
            telMobile: (string) ($data['tel_mobile'] ?? ''),
            skype: (string) ($data['skype'] ?? ''),
            facebook: (string) ($data['facebook'] ?? ''),
            twitter: (string) ($data['twitter'] ?? ''),
            avatarImageUrl: (string) ($data['avatar_image_url'] ?? ''),
            loginMail: (string) ($data['login_mail'] ?? ''),
        );
    }
}
