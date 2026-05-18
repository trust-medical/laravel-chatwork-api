<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('Connection::make はデフォルト値を持つ値オブジェクトを生成する', function () {
    $conn = Connection::make('tenant-1', new ApiTokenCredentials('t'));

    expect($conn)->toBeInstanceOf(Connection::class)
        ->and($conn->name)->toBe('tenant-1')
        ->and($conn->credentials)->toBeInstanceOf(ApiTokenCredentials::class)
        ->and($conn->baseUri)->toBe('https://api.chatwork.com/v2')
        ->and($conn->timeout)->toBe(10);
});

it('Connection::make はカスタムの baseUri と timeout を受け付ける', function () {
    $conn = Connection::make(
        'tenant-2',
        new BearerTokenCredentials('t'),
        baseUri: 'https://api.example.com/v3',
        timeout: 30,
    );

    expect($conn->baseUri)->toBe('https://api.example.com/v3')
        ->and($conn->timeout)->toBe(30);
});

it('config からデフォルト接続を解決する (P1-T04)', function () {
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

it('config から名前付き bearer 接続を解決する (P1-T05)', function () {
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

it('forConnection 経由で Connection 値オブジェクトを受け付ける (P1-T06)', function () {
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

it('存在しない接続名に対して ChatworkAuthenticationException をスローする', function () {
    Chatwork::connection('does-not-exist');
})->throws(ChatworkAuthenticationException::class);

it('認識できない auth ドライバーに対して例外をスローする', function () {
    config()->set('chatwork.connections.broken', [
        'auth' => 'magic',
        'token' => 't',
    ]);

    Chatwork::connection('broken');
})->throws(ChatworkAuthenticationException::class);
