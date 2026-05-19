<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Auth\BearerTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkAuthenticationException;
use TrustMedical\LaravelChatworkApi\Facades\Chatwork;

it('var_dump 出力で credentials を再帰展開せず FQCN 文字列で表す (__debugInfo)', function () {
    $conn = Connection::make('tenant-x', new ApiTokenCredentials('secret-token-value'));

    ob_start();
    var_dump($conn);
    $dump = (string) ob_get_clean();

    expect(str_contains($dump, 'secret-token-value'))->toBeFalse();
    expect(str_contains($dump, 'object(' . ApiTokenCredentials::class . ')'))->toBeFalse();
    expect($dump)
        ->toContain('"' . ApiTokenCredentials::class . '"')
        ->toContain('tenant-x')
        ->toContain('https://api.chatwork.com/v2');
});

it('Connection::make は http(s) 以外の baseUri スキームを拒否する', function () {
    Connection::make('x', new ApiTokenCredentials('t'), baseUri: 'file:///etc/passwd');
})->throws(InvalidArgumentException::class);

it('Connection::make は http スキームの baseUri を許可する', function () {
    $conn = Connection::make('x', new ApiTokenCredentials('t'), baseUri: 'http://localhost:8080/v2');

    expect($conn->baseUri)->toBe('http://localhost:8080/v2');
});

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
});

it('config から名前付き bearer 接続を解決する (P1-T05)', function () {
    config()->set('chatwork.connections.sales', [
        'auth' => 'bearer',
        'token' => 'sales-bearer',
    ]);

    $conn = Chatwork::connection('sales')->getEffectiveConnection();

    expect($conn->name)->toBe('sales')
        ->and($conn->credentials)->toBeInstanceOf(BearerTokenCredentials::class);
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
