<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('Connection::make builds a value object with defaults', function () {
    $conn = Connection::make('tenant-1', new ApiTokenCredentials('t'));

    expect($conn)->toBeInstanceOf(Connection::class)
        ->and($conn->name)->toBe('tenant-1')
        ->and($conn->credentials)->toBeInstanceOf(ApiTokenCredentials::class)
        ->and($conn->baseUri)->toBe('https://api.chatwork.com/v2')
        ->and($conn->timeout)->toBe(10);
});

it('Connection::make accepts custom baseUri and timeout', function () {
    $conn = Connection::make(
        'tenant-2',
        new BearerTokenCredentials('t'),
        baseUri: 'https://api.example.com/v3',
        timeout: 30,
    );

    expect($conn->baseUri)->toBe('https://api.example.com/v3')
        ->and($conn->timeout)->toBe(30);
});

it('resolves the default connection from config (P1-T04)', function () {
    config()->set('chatwork.connections.default', [
        'auth' => 'api_token',
        'token' => 'default-token',
    ]);

    $manager = Chatwork::connection();
    $conn = $manager->getEffectiveConnection();

    expect($conn->name)->toBe('default')
        ->and($conn->credentials)->toBeInstanceOf(ApiTokenCredentials::class);

    /** @var ApiTokenCredentials $creds */
    $creds = $conn->credentials;
    expect($creds->token)->toBe('default-token');
});

it('resolves a named bearer connection from config (P1-T05)', function () {
    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'sales-bearer',
    ]);

    $conn = Chatwork::connection('sales')->getEffectiveConnection();

    expect($conn->name)->toBe('sales')
        ->and($conn->credentials)->toBeInstanceOf(BearerTokenCredentials::class);

    /** @var BearerTokenCredentials $creds */
    $creds = $conn->credentials;
    expect($creds->token)->toBe('sales-bearer');
});

it('accepts a Connection value object via forConnection (P1-T06)', function () {
    $custom = Connection::make(
        'dynamic-tenant',
        new BearerTokenCredentials('runtime-bearer'),
        baseUri: 'https://api.chatwork.com/v2',
        timeout: 15,
    );

    $conn = Chatwork::forConnection($custom)->getEffectiveConnection();

    expect($conn)->toBe($custom)
        ->and($conn->timeout)->toBe(15);
});

it('throws ChatworkAuthenticationException for an unknown connection', function () {
    Chatwork::connection('does-not-exist');
})->throws(ChatworkAuthenticationException::class);

it('throws when the auth driver is unrecognised', function () {
    config()->set('chatwork.connections.broken', [
        'auth' => 'magic',
        'token' => 't',
    ]);

    Chatwork::connection('broken');
})->throws(ChatworkAuthenticationException::class);
