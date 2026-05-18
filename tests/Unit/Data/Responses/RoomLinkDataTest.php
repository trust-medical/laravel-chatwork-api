<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Responses\RoomLinkData;

it('is a readonly class', function () {
    expect(RoomLinkData::class)->toBeReadonly();
});

it('hydrates RoomLinkData via fromArray', function () {
    $data = fixtureJson('links/get-room-link-200.json');

    $link = RoomLinkData::fromArray($data);

    expect($link->public)->toBeTrue();
    expect($link->url)->toBe('https://www.chatwork.com/g/abcdef');
    expect($link->needAcceptance)->toBeTrue();
    expect($link->description)->toBe('Join our project room');
});

it('casts public and need_acceptance to bool', function () {
    $link = RoomLinkData::fromArray([
        'public' => 1,
        'need_acceptance' => 0,
    ]);

    expect($link->public)->toBeTrue();
    expect($link->needAcceptance)->toBeFalse();
});

it('defaults missing optional fields', function () {
    $link = RoomLinkData::fromArray(['public' => false]);

    expect($link->public)->toBeFalse();
    expect($link->url)->toBe('');
    expect($link->needAcceptance)->toBeFalse();
    expect($link->description)->toBe('');
});
