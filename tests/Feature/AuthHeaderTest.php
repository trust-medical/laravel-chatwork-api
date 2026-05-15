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

it('sends x-chatworktoken when withApiToken is used (P1-T07)', function () {
    $manager = Chatwork::withApiToken('runtime-api-token');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'runtime-api-token')
        && ! $r->hasHeader('Authorization')
    );
});

it('sends Authorization Bearer when withBearerToken is used (P1-T08)', function () {
    $manager = Chatwork::withBearerToken('runtime-bearer');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer runtime-bearer')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('never sends both auth headers when both methods are chained (P1-T09)', function () {
    $manager = Chatwork::withApiToken('first')->withBearerToken('second');

    app(ChatworkPendingRequestFactory::class)
        ->create($manager->getEffectiveConnection())
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer second')
        && ! $r->hasHeader('x-chatworktoken')
    );
});

it('falls back to default connection auth when no override is set', function () {
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

it('keeps base_uri / timeout from the underlying connection when overriding token', function () {
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
