<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Auth\ApiTokenCredentials;
use TrustMedical\LaravelChatworkApi\Connection;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkRoute;

it('exposes roomId via room()', function () {
    $route = ChatworkRoute::room(123);

    expect($route->roomId())->toBe(123)
        ->and($route->connectionName())->toBeNull()
        ->and($route->getConnection())->toBeNull();
});

it('accepts a string roomId', function () {
    $route = ChatworkRoute::room('456');

    expect($route->roomId())->toBe('456');
});

it('attaches a named connection via connection()', function () {
    $route = ChatworkRoute::room(123)->connection('sales');

    expect($route->connectionName())->toBe('sales')
        ->and($route->getConnection())->toBeNull();
});

it('attaches a Connection value object via using()', function () {
    $connection = Connection::make('tenant-1', new ApiTokenCredentials('t'));
    $route = ChatworkRoute::room(123)->using($connection);

    expect($route->getConnection())->toBe($connection)
        ->and($route->connectionName())->toBeNull();
});

it('uses last-wins semantics across connection() and using()', function () {
    $connection = Connection::make('tenant-2', new ApiTokenCredentials('t'));

    $afterConnection = ChatworkRoute::room(123)->connection('sales')->using($connection);
    expect($afterConnection->getConnection())->toBe($connection)
        ->and($afterConnection->connectionName())->toBeNull();

    $afterUsing = ChatworkRoute::room(123)->using($connection)->connection('sales');
    expect($afterUsing->connectionName())->toBe('sales')
        ->and($afterUsing->getConnection())->toBeNull();
});

it('is immutable across modifier calls', function () {
    $original = ChatworkRoute::room(123);
    $modified = $original->connection('sales');

    expect($original->connectionName())->toBeNull()
        ->and($modified->connectionName())->toBe('sales')
        ->and($modified)->not->toBe($original);
});
