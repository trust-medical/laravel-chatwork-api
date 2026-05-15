<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

enum ResponseMode: string
{
    case Array = 'array';
    case Dto = 'dto';
    case Collection = 'collection';
    case Response = 'response';
    case PsrResponse = 'psr_response';
    case Result = 'result';
}
