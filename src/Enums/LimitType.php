<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

enum LimitType: string
{
    case None = 'none';
    case Date = 'date';
    case Time = 'time';
}
