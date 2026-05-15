<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Facades;

use Illuminate\Support\Facades\Facade;
use TrustMedical\LaravelChatworkApi\ChatworkManager;

/**
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager connection(?string $name = null)
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager forConnection(\TrustMedical\LaravelChatworkApi\Connection $connection)
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager withApiToken(string $token)
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager withBearerToken(string $token)
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asArray()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asDto()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asCollection()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asResponse()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asPsrResponse()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkManager asResult()
 * @method static \TrustMedical\LaravelChatworkApi\Resources\RoomsResource rooms()
 * @method static \TrustMedical\LaravelChatworkApi\Resources\MeResource me()
 * @method static \TrustMedical\LaravelChatworkApi\Resources\MyResource my()
 * @method static \TrustMedical\LaravelChatworkApi\Resources\ContactsResource contacts()
 * @method static \TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource incomingRequests()
 *
 * @see ChatworkManager
 */
final class Chatwork extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'chatwork';
    }
}
