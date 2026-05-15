<?php

declare(strict_types=1);

use TrustMedical\LaravelChatworkApi\Data\Requests\CreateRoomRequest;
use TrustMedical\LaravelChatworkApi\Enums\IconPreset;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

it('rejects an empty name', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: '', membersAdminIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects a name longer than 255 characters', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: str_repeat('a', 256), membersAdminIds: [1]);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('rejects empty membersAdminIds', function () {
    $caught = null;
    try {
        new CreateRoomRequest(name: 'Team', membersAdminIds: []);
    } catch (ChatworkValidationException $e) {
        $caught = $e;
    }

    expect($caught)->toBeInstanceOf(ChatworkValidationException::class);
});

it('serializes membersAdminIds as a CSV string', function () {
    $request = new CreateRoomRequest(name: 'Team', membersAdminIds: [1, 2, 3]);

    expect($request->toArray()['members_admin_ids'])->toBe('1,2,3');
});

it('serializes IconPreset enum to its string value', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1],
        iconPreset: IconPreset::Business,
    );

    expect($request->toArray()['icon_preset'])->toBe('business');
});

it('converts link and link_need_acceptance bool to 0/1', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1],
        link: true,
        linkNeedAcceptance: false,
    );

    $payload = $request->toArray();

    expect($payload['link'])->toBe(1);
    expect($payload['link_need_acceptance'])->toBe(0);
});

it('omits optional fields when null', function () {
    $request = new CreateRoomRequest(name: 'Team', membersAdminIds: [1]);

    $payload = $request->toArray();

    expect($payload)->toHaveKeys(['name', 'members_admin_ids']);
    expect($payload)->not->toHaveKey('description');
    expect($payload)->not->toHaveKey('link');
    expect($payload)->not->toHaveKey('icon_preset');
    expect($payload)->not->toHaveKey('members_member_ids');
    expect($payload)->not->toHaveKey('members_readonly_ids');
});

it('serializes members_member_ids and members_readonly_ids as CSV', function () {
    $request = new CreateRoomRequest(
        name: 'Team',
        membersAdminIds: [1, 2],
        membersMemberIds: [3, 4],
        membersReadonlyIds: [5],
    );

    $payload = $request->toArray();

    expect($payload['members_member_ids'])->toBe('3,4');
    expect($payload['members_readonly_ids'])->toBe('5');
});
