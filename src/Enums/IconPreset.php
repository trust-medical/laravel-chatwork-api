<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

/**
 * ルームの組み込みアイコンプリセット。
 *
 * バッキング値は Chatwork のルーム作成・更新エンドポイントが受け付ける
 * `icon_preset` パラメータに対応する。
 */
enum IconPreset: string
{
    case Group = 'group';
    case Check = 'check';
    case Document = 'document';
    case Meeting = 'meeting';
    case Event = 'event';
    case Project = 'project';
    case Business = 'business';
    case Study = 'study';
    case Security = 'security';
    case Star = 'star';
    case Idea = 'idea';
    case Heart = 'heart';
    case Magcup = 'magcup';
    case Beer = 'beer';
    case Music = 'music';
    case Sports = 'sports';
    case Travel = 'travel';
}
