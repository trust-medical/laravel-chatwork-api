<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;

function chatworkPendingFactory(): ChatworkPendingRequestFactory
{
    return app(ChatworkPendingRequestFactory::class);
}

it('applies base_uri from connection (P1-T10)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make('default', new ApiTokenCredentials('t'));
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.chatwork.com/v2/rooms');
});

it('uses a custom base_uri when the connection overrides it', function () {
    Http::fake([
        'https://api.example.com/v3/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make(
        'tenant',
        new ApiTokenCredentials('t'),
        baseUri: 'https://api.example.com/v3',
    );
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->url() === 'https://api.example.com/v3/rooms');
});

it('sends Accept: application/json', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make('default', new ApiTokenCredentials('t'));
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Accept', 'application/json'));
});

it('sends a User-Agent containing the package name', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make('default', new ApiTokenCredentials('t'));
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(function (Request $r): bool {
        $ua = $r->header('User-Agent')[0] ?? '';

        return str_contains($ua, 'trust-medical/laravel-chatwork-api')
            && str_contains($ua, 'Laravel/')
            && str_contains($ua, 'PHP/');
    });
});

it('applies ApiTokenCredentials (x-chatworktoken)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make('default', new ApiTokenCredentials('api-token-value'));
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'api-token-value'));
});

it('applies BearerTokenCredentials (Authorization Bearer)', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $conn = Connection::make('default', new BearerTokenCredentials('bearer-value'));
    chatworkPendingFactory()->create($conn)->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer bearer-value'));
});
