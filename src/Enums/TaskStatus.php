<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
}
