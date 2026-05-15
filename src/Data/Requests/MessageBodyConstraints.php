<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

final class MessageBodyConstraints
{
    public const int BODY_MIN = 1;

    public const int BODY_MAX = 65535;
}
