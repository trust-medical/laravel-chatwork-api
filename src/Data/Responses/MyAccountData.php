<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

final readonly class MyAccountData
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
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
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
