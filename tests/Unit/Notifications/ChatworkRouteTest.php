<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

it('room() で roomId を公開する', function () {
    $route = ChatworkRoute::room(123);

    expect($route->roomId())->toBe(123)
        ->and($route->connectionName())->toBeNull()
        ->and($route->getConnection())->toBeNull();
});

it('文字列の roomId を受け入れる', function () {
    $route = ChatworkRoute::room('456');

    expect($route->roomId())->toBe('456');
});

it('connection() で名前付きコネクションを紐付ける', function () {
    $route = ChatworkRoute::room(123)->connection('sales');

    expect($route->connectionName())->toBe('sales')
        ->and($route->getConnection())->toBeNull();
});

it('using() で Connection 値オブジェクトを紐付ける', function () {
    $connection = Connection::make('tenant-1', new ApiTokenCredentials('t'));
    $route = ChatworkRoute::room(123)->using($connection);

    expect($route->getConnection())->toBe($connection)
        ->and($route->connectionName())->toBeNull();
});

it('connection() と using() で後勝ちセマンティクスを使用する', function () {
    $connection = Connection::make('tenant-2', new ApiTokenCredentials('t'));

    $afterConnection = ChatworkRoute::room(123)->connection('sales')->using($connection);
    expect($afterConnection->getConnection())->toBe($connection)
        ->and($afterConnection->connectionName())->toBeNull();

    $afterUsing = ChatworkRoute::room(123)->using($connection)->connection('sales');
    expect($afterUsing->connectionName())->toBe('sales')
        ->and($afterUsing->getConnection())->toBeNull();
});

it('修飾メソッドの呼び出しをまたいでイミュータブルである', function () {
    $original = ChatworkRoute::room(123);
    $modified = $original->connection('sales');

    expect($original->connectionName())->toBeNull()
        ->and($modified->connectionName())->toBe('sales')
        ->and($modified)->not->toBe($original);
});
