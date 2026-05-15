<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Auth;

use Illuminate\Http\Client\PendingRequest;

interface Credentials
{
    public function applyTo(PendingRequest $request): PendingRequest;
}
