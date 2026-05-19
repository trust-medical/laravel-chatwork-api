<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

/**
 * ルームの種別。
 *
 * バッキング値は Chatwork ルームオブジェクトの `type` フィールドに対応する。
 */
enum RoomType: string
{
    case My = 'my';
    case Direct = 'direct';
    case Group = 'group';
}
