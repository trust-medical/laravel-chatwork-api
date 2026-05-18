<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Facades;

use Illuminate\Support\Facades\Facade;
use TrustMedical\LaravelChatworkApi\ChatworkManager;

/**
 * バインドされた `chatwork` ChatworkManager にプロキシするスタティックエントリポイント。
 *
 * connection 切り替えおよびレスポンスモードメソッドは新しい ChatworkManager を返す
 * （manager はイミュータブルで各呼び出しはクローンを生成）ため、resource accessor の前に
 * チェーンできる。Resource accessor は解決済み connection 上に ChatworkClient を構築し、
 * 対応するリソースを fluent API のルートとして返す。
 *
 * @method static ChatworkManager connection(?string $name = null)
 * @method static ChatworkManager forConnection(\TrustMedical\LaravelChatworkApi\Connection $connection)
 * @method static ChatworkManager withApiToken(string $token)
 * @method static ChatworkManager withBearerToken(string $token)
 * @method static ChatworkManager asArray()
 * @method static ChatworkManager asDto()
 * @method static ChatworkManager asCollection()
 * @method static ChatworkManager asResponse()
 * @method static ChatworkManager asPsrResponse()
 * @method static ChatworkManager asResult()
 * @method static \TrustMedical\LaravelChatworkApi\ChatworkClient client()
 * @method static \TrustMedical\LaravelChatworkApi\Connection getConnection()
 * @method static \TrustMedical\LaravelChatworkApi\Connection getEffectiveConnection()
 * @method static \TrustMedical\LaravelChatworkApi\Enums\ResponseMode getMode()
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
