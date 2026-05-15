<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;

interface TokenProvider
{
    /**
     * @throws ChatworkAuthenticationException
     */
    public function credentials(): Credentials;
}
