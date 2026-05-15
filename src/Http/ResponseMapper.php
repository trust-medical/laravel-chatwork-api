<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Http;

use Illuminate\Http\Client\Response;

final class ResponseMapper
{
    /**
     * @param  class-string|null  $dtoClass
     */
    public function map(Response $response, string $mode, ?string $dtoClass = null): mixed
    {
        throw new \LogicException(sprintf(
            'not implemented in Phase 0 (status=%d, mode=%s, dto=%s)',
            $response->status(),
            $mode,
            $dtoClass ?? 'null',
        ));
    }
}
