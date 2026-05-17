<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

enum RoomRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Readonly = 'readonly';
}
