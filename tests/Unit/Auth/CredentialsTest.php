<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;

it('ApiTokenCredentials は x-chatworktoken ヘッダーを付与する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    $credentials = new ApiTokenCredentials('my-token');
    $pending = $credentials->applyTo(Http::baseUrl('https://api.chatwork.com/v2'));

    $pending->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('x-chatworktoken', 'my-token'));
});

it('ApiTokenCredentials は Authorization ヘッダーを付与しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    (new ApiTokenCredentials('my-token'))
        ->applyTo(Http::baseUrl('https://api.chatwork.com/v2'))
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('Authorization'));
});

it('BearerTokenCredentials は Authorization Bearer ヘッダーを付与する', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    (new BearerTokenCredentials('oauth-token'))
        ->applyTo(Http::baseUrl('https://api.chatwork.com/v2'))
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer oauth-token'));
});

it('BearerTokenCredentials は x-chatworktoken ヘッダーを付与しない', function () {
    Http::fake([
        'https://api.chatwork.com/v2/rooms' => Http::response([], 200),
    ]);

    (new BearerTokenCredentials('oauth-token'))
        ->applyTo(Http::baseUrl('https://api.chatwork.com/v2'))
        ->get('/rooms');

    Http::assertSent(fn (Request $r) => ! $r->hasHeader('x-chatworktoken'));
});

it('ApiTokenCredentials は var_dump でトークンを露出しない', function () {
    $credentials = new ApiTokenCredentials('secret-api-token-value');

    ob_start();
    var_dump($credentials);
    $dump = (string) ob_get_clean();

    expect($dump)->not->toContain('secret-api-token-value')
        ->and($dump)->toContain('***redacted***');
});

it('BearerTokenCredentials は var_dump でトークンを露出しない', function () {
    $credentials = new BearerTokenCredentials('secret-bearer-token-value');

    ob_start();
    var_dump($credentials);
    $dump = (string) ob_get_clean();

    expect($dump)->not->toContain('secret-bearer-token-value')
        ->and($dump)->toContain('***redacted***');
});
