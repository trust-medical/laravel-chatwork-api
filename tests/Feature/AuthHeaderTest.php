<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;

beforeEach(function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);
});

it('withApiToken 使用時に x-chatworktoken を送信する (P1-T07)', function () {
    $manager = Chatwork::withApiToken('runtime-api-token');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'runtime-api-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('withBearerToken 使用時に Authorization Bearer を送信する (P1-T08)', function () {
    $manager = Chatwork::withBearerToken('runtime-bearer');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer runtime-bearer')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('両メソッドをチェーンしても認証ヘッダーを同時に送信しない (P1-T09)', function () {
    $manager = Chatwork::withApiToken('first')->withBearerToken('second');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer second')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('オーバーライドが未設定の場合はデフォルト接続の認証にフォールバックする', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-api-token',
    ]);

    $manager = Chatwork::connection();

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'default-api-token'));
});

it('トークンをオーバーライドしても base_uri / timeout は元の接続の値を保持する', function () {
    config()->set('chatwork.connections.tenant', [
        'auth' => 'bearer',
        'token' => 'tenant-bearer',
    ]);

    $manager = Chatwork::connection('tenant')->withApiToken('override-api-token');
    $effective = $manager->getEffectiveConnection();

    expect($effective->name)->toBe('tenant')
        ->and($effective->baseUri)->toBe('https://api.chatwork.com/v2');

    app(ChatworkPendingRequestFactory::class)
        ->create($effective)
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'override-api-token')
        && ! $r->hasHeader('Authorization')
    );
});
