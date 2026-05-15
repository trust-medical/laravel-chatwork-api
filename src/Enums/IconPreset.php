<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

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
